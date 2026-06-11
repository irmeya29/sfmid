<?php

namespace App\Http\Controllers;

use App\Actions\Documents\ConvertDeliveryNoteToInvoiceAction;
use App\Actions\Documents\CreateInvoiceFromCommercialSourceAction;
use App\Actions\Documents\SaveDirectInvoiceAction;
use App\Enums\InvoiceStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Proforma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['client', 'deliveryNote', 'proforma', 'customerOrder', 'creator'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::options(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Invoice::class);

        $deliveryNotes = DeliveryNote::query()
            ->with('client')
            ->delivered()
            ->doesntHave('invoice')
            ->latest()
            ->get();

        $proformas = Proforma::query()
            ->with('client')
            ->validated()
            ->whereDoesntHave('invoice')
            ->whereDoesntHave('customerOrder')
            ->whereDoesntHave('deliveryNote')
            ->latest()
            ->get();

        return view('invoices.create', [
            'deliveryNotes' => $deliveryNotes,
            'proformas' => $proformas,
            'selectedDeliveryNoteId' => $request->integer('delivery_note_id') ?: null,
            'selectedProformaId' => $request->integer('proforma_id') ?: null,
            'clients' => Client::query()->active()->orderBy('name')->get(),
            'company' => CompanySetting::query()->pluck('value', 'key')->all(),
            'lineItems' => [
                [
                    'product_id' => '',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'discount_amount' => 0,
                    'tax_rate' => 0,
                ],
            ],
        ]);
    }

    public function store(
        StoreInvoiceRequest $request,
        ConvertDeliveryNoteToInvoiceAction $convertDeliveryNoteToInvoice,
        CreateInvoiceFromCommercialSourceAction $createInvoiceFromCommercialSource,
        SaveDirectInvoiceAction $saveDirectInvoice
    ): RedirectResponse {
        Gate::authorize('create', Invoice::class);

        $data = $request->validated();

        if ($data['source_type'] === 'delivery_note') {
            try {
                $deliveryNote = DeliveryNote::query()->findOrFail($data['delivery_note_id']);
                $invoice = $convertDeliveryNoteToInvoice->execute($deliveryNote, $request->user());
            } catch (RuntimeException $exception) {
                return back()->with('error', $exception->getMessage())->withInput();
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Facture créée depuis le BL livré. Aucun stock redéduit.');
        }

        if ($data['source_type'] === 'proforma') {
            try {
                $invoice = $createInvoiceFromCommercialSource->fromProforma(
                    Proforma::query()->findOrFail($data['proforma_id']),
                    $request->user()
                );
            } catch (RuntimeException $exception) {
                return back()->with('error', $exception->getMessage())->withInput();
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Facture creee depuis la proforma. Aucun paiement automatique.');
        }

        $invoice = $saveDirectInvoice->execute($data, $request->user());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Facture directe créée. Aucun mouvement de stock effectué.');
    }

    public function storeFromProforma(Proforma $proforma, Request $request, CreateInvoiceFromCommercialSourceAction $action): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        try {
            $invoice = $action->fromProforma($proforma, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Facture creee depuis la proforma. Aucun paiement automatique.');
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load([
            'client',
            'deliveryNote.deliverySite',
            'proforma',
            'customerOrder',
            'items.product',
            'payments.creator',
            'payments.validator',
            'validationHistories.user',
            'creator',
            'validator',
            'rejector',
            'stockSuspenses.product',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        Gate::authorize('update', $invoice);

        return view('invoices.edit', compact('invoice'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $invoice->forceFill($request->validated())->save();

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Facture modifiée avec succès.');
    }

    public function pdf(Invoice $invoice): Response
    {
        Gate::authorize('exportPdf', $invoice);

        $invoice->load(['client', 'deliveryNote.deliverySite', 'proforma', 'customerOrder', 'items.product', 'payments']);

        $company = CompanySetting::query()->pluck('value', 'key')->all();

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => $company,
        ])->setPaper('a4')->stream($invoice->number.'.pdf');
    }
}
