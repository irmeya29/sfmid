<?php

namespace App\Http\Controllers;

use App\Actions\Documents\SaveProformaAction;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreProformaRequest;
use App\Http\Requests\UpdateProformaRequest;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Proforma;
use App\Services\Audit\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProformaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Proforma::class);

        $proformas = Proforma::query()
            ->with(['client', 'creator'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('proformas.index', [
            'proformas' => $proformas,
            'statuses' => DocumentStatus::options(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Proforma::class);

        $company = CompanySetting::query()->pluck('value', 'key')->all();

        return view('proformas.create', [
            'proforma' => new Proforma([
                'issue_date' => now(),
                'valid_until' => now()->addDays(15),
                'currency' => $company['sales.currency'] ?? 'FCFA',
            ]),
            'clients' => Client::query()->with('deliverySites')->active()->orderBy('name')->get(),
            'products' => Product::query()->with('clientPrices')->active()->commercial()->orderBy('name')->get(),
            'company' => $company,
            'lineItems' => [
                [
                    'product_id' => '',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'discount_rate' => 0,
                    'tax_rate' => (float) ($company['sales.default_tax_rate'] ?? 0),
                ],
            ],
        ]);
    }

    public function store(StoreProformaRequest $request, SaveProformaAction $saveProformaAction): RedirectResponse
    {
        Gate::authorize('create', Proforma::class);

        $proforma = $saveProformaAction->execute(
            data: $request->validated(),
            user: $request->user(),
        );

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma créée avec succès.');
    }

    public function show(Proforma $proforma): View
    {
        Gate::authorize('view', $proforma);

        $proforma->load([
            'client',
            'deliverySite',
            'items.product',
            'creator',
            'validator',
            'rejector',
            'validationHistories.user',
            'deliveryNote',
        ]);

        return view('proformas.show', compact('proforma'));
    }

    public function edit(Proforma $proforma): View
    {
        Gate::authorize('update', $proforma);

        $proforma->load('items');

        return view('proformas.edit', [
            'proforma' => $proforma,
            'clients' => Client::query()->with('deliverySites')->active()->orderBy('name')->get(),
            'products' => Product::query()->with('clientPrices')->active()->commercial()->orderBy('name')->get(),
            'company' => CompanySetting::query()->pluck('value', 'key')->all(),
            'lineItems' => $proforma->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_rate' => (float) $item->discount_rate,
                'tax_rate' => (float) ($item->tax_rate ?? 0),
            ])->values()->all(),
        ]);
    }

    public function update(UpdateProformaRequest $request, Proforma $proforma, SaveProformaAction $saveProformaAction): RedirectResponse
    {
        Gate::authorize('update', $proforma);

        $proforma = $saveProformaAction->execute(
            data: $request->validated(),
            user: $request->user(),
            proforma: $proforma,
        );

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma modifiée avec succès.');
    }

    public function pdf(Proforma $proforma): Response
    {
        Gate::authorize('exportPdf', $proforma);

        $proforma->load([
            'client',
            'deliverySite',
            'items.product',
            'creator',
            'validator',
        ]);

        $company = CompanySetting::query()->pluck('value', 'key')->all();

        return Pdf::loadView('proformas.pdf', [
            'proforma' => $proforma,
            'company' => $company,
        ])
            ->setPaper('a4')
            ->stream($proforma->number.'.pdf');
    }

    public function destroy(Proforma $proforma, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('delete', $proforma);

        DB::transaction(function () use ($proforma, $activityLogger): void {
            $activityLogger->log(
                action: 'deleted',
                module: 'proformas',
                description: "Proforma {$proforma->number} supprimée.",
                subject: $proforma,
                oldValues: $proforma->only(['number', 'status', 'total']),
            );

            $proforma->delete();
        });

        return redirect()
            ->route('proformas.index')
            ->with('success', 'Proforma supprimée avec succès.');
    }
}
