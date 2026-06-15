<?php

namespace App\Http\Controllers;

use App\Actions\Documents\SaveDeliveryNoteAction;
use App\Enums\DeliveryNoteStatus;
use App\Http\Requests\StoreDeliveryNoteRequest;
use App\Http\Requests\UpdateDeliveryNoteRequest;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\DeliveryNote;
use App\Models\Product;
use App\Models\StockSite;
use App\Services\Audit\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DeliveryNoteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DeliveryNote::class);

        $deliveryNotes = DeliveryNote::query()
            ->with(['client', 'creator', 'proforma', 'customerOrder'])
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

        return view('delivery-notes.index', [
            'deliveryNotes' => $deliveryNotes,
            'statuses' => DeliveryNoteStatus::options(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', DeliveryNote::class);

        return view('delivery-notes.create', [
            'deliveryNote' => new DeliveryNote([
                'planned_delivery_date' => now()->addDay(),
            ]),
            'clients' => Client::query()->with('deliverySites')->active()->orderBy('name')->get(),
            'products' => Product::query()->with('stockSiteStocks')->active()->commercial()->orderBy('name')->get(),
            'stockSites' => StockSite::query()->active()->sellable()->orderBy('name')->get(),
            'lineItems' => [
                [
                    'product_id' => '',
                    'quantity' => 1,
                    'delivered_quantity' => 1,
                    'unit_price' => 0,
                    'discount_amount' => 0,
                ],
            ],
        ]);
    }

    public function store(StoreDeliveryNoteRequest $request, SaveDeliveryNoteAction $action): RedirectResponse
    {
        Gate::authorize('create', DeliveryNote::class);

        $deliveryNote = $action->execute(
            data: $request->validated(),
            user: $request->user(),
        );

        return redirect()
            ->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'BL créé avec succès.');
    }

    public function show(DeliveryNote $deliveryNote): View
    {
        Gate::authorize('view', $deliveryNote);

        $deliveryNote->load([
            'client',
            'deliverySite',
            'items.product',
            'creator',
            'validator',
            'rejector',
            'deliverer',
            'stockMover',
            'stockSite',
            'proforma',
            'customerOrder',
            'invoice',
            'stockSuspenses.product',
            'validationHistories.user',
        ]);

        return view('delivery-notes.show', compact('deliveryNote'));
    }

    public function edit(DeliveryNote $deliveryNote): View
    {
        Gate::authorize('update', $deliveryNote);

        $deliveryNote->load('items');

        return view('delivery-notes.edit', [
            'deliveryNote' => $deliveryNote,
            'clients' => Client::query()->with('deliverySites')->active()->orderBy('name')->get(),
            'products' => Product::query()->with('stockSiteStocks')->active()->commercial()->orderBy('name')->get(),
            'stockSites' => StockSite::query()->active()->sellable()->orderBy('name')->get(),
            'lineItems' => $deliveryNote->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'delivered_quantity' => (float) $item->delivered_quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
            ])->values()->all(),
        ]);
    }

    public function update(UpdateDeliveryNoteRequest $request, DeliveryNote $deliveryNote, SaveDeliveryNoteAction $action): RedirectResponse
    {
        Gate::authorize('update', $deliveryNote);

        $deliveryNote = $action->execute(
            data: $request->validated(),
            user: $request->user(),
            deliveryNote: $deliveryNote,
        );

        return redirect()
            ->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'BL modifié avec succès.');
    }

    public function pdf(DeliveryNote $deliveryNote): Response
    {
        Gate::authorize('exportPdf', $deliveryNote);

        $deliveryNote->load([
            'client',
            'deliverySite',
            'items.product',
            'creator',
            'validator',
            'deliverer',
            'stockSite',
            'proforma',
            'customerOrder',
        ]);

        $company = CompanySetting::query()->pluck('value', 'key')->all();

        return Pdf::loadView('delivery-notes.pdf', [
            'deliveryNote' => $deliveryNote,
            'company' => $company,
        ])
            ->setPaper('a4')
            ->stream($deliveryNote->number.'.pdf');
    }

    public function destroy(DeliveryNote $deliveryNote, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('delete', $deliveryNote);

        DB::transaction(function () use ($deliveryNote, $activityLogger): void {
            $activityLogger->log(
                action: 'deleted',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} supprimé.",
                subject: $deliveryNote,
                oldValues: $deliveryNote->only(['number', 'status', 'total']),
            );

            $deliveryNote->delete();
        });

        return redirect()
            ->route('delivery-notes.index')
            ->with('success', 'BL supprimé avec succès.');
    }
}
