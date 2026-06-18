<?php

namespace App\Http\Controllers;

use App\Actions\Documents\ConvertCustomerOrderToDeliveryNoteAction;
use App\Actions\Documents\ConvertProformaToCustomerOrderAction;
use App\Actions\Documents\CreateInvoiceFromCommercialSourceAction;
use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\CustomerOrder;
use App\Models\Proforma;
use App\Models\Product;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = CustomerOrder::query()
            ->with(['client', 'proforma', 'deliveryNotes', 'invoices'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_reference', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customer-orders.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        $proforma = $request->filled('proforma_id')
            ? Proforma::query()->with(['client', 'deliverySite', 'items'])->findOrFail($request->integer('proforma_id'))
            : null;

        return view('customer-orders.create', [
            'proforma' => $proforma,
            'clients' => Client::query()->with('deliverySites')->active()->orderBy('name')->get(),
            'products' => Product::query()->active()->commercial()->orderBy('name')->get(),
        ]);
    }

    public function store(
        Request $request,
        DocumentNumberGenerator $numbers,
        ActivityLogger $logger,
        ConvertProformaToCustomerOrderAction $convertProformaToCustomerOrder
    ): RedirectResponse {
        $data = $request->validate([
            'source_type' => ['required', 'in:proforma,direct'],
            'proforma_id' => ['required_if:source_type,proforma', 'nullable', 'integer', 'exists:proformas,id'],
            'client_id' => ['required_if:source_type,direct', 'nullable', 'integer', 'exists:clients,id'],
            'client_delivery_site_id' => ['nullable', 'integer', 'exists:client_delivery_sites,id'],
            'customer_reference' => ['nullable', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'confirmed_terms' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,bmp,tif,tiff', 'max:10240'],
            'items' => ['required_if:source_type,direct', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $attachmentPath = $request->file('attachment')?->store('customer-orders', 'local');

        if ($data['source_type'] === 'proforma') {
            try {
                $order = $convertProformaToCustomerOrder->execute(
                    Proforma::query()->findOrFail($data['proforma_id']),
                    $request->user(),
                    [
                        ...$data,
                        'attachment_path' => $attachmentPath,
                    ]
                );
            } catch (RuntimeException $exception) {
                return back()->with('error', $exception->getMessage())->withInput();
            }

            return redirect()->route('customer-orders.show', $order)->with('success', 'Bon de commande client cree depuis la proforma.');
        }

        try {
            $order = DB::transaction(function () use ($data, $request, $numbers, $logger, $attachmentPath): CustomerOrder {
            $prepared = [];
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }

                $product = Product::query()->findOrFail($item['product_id']);
                $quantity = (float) $item['quantity'];
                if ($quantity <= 0) {
                    throw new RuntimeException("Quantite invalide pour {$product->name}.");
                }
                $unitPrice = isset($item['unit_price']) && (float) $item['unit_price'] > 0 ? (float) $item['unit_price'] : (float) $product->sale_price;
                $lineTotal = round($quantity * $unitPrice, 2);
                $subtotal += $lineTotal;

                $prepared[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_internal_reference' => $product->internal_reference,
                    'client_product_reference' => null,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_subtotal' => $lineTotal,
                    'line_total_ht' => $lineTotal,
                    'line_total_ttc' => $lineTotal,
                    'line_total' => $lineTotal,
                ];
            }

            if ($prepared === []) {
                throw new RuntimeException('Ajoutez au moins un article au bon de commande client.');
            }

            $order = CustomerOrder::query()->create([
                'number' => $numbers->generate('customer_order'),
                'client_id' => $data['client_id'],
                'client_delivery_site_id' => $data['client_delivery_site_id'] ?? null,
                'customer_reference' => $data['customer_reference'] ?? null,
                'order_date' => $data['order_date'],
                'status' => DocumentStatus::Validated,
                'confirmed_terms' => $data['confirmed_terms'] ?? null,
                'attachment_path' => $attachmentPath,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'created_by' => $request->user()->id,
            ]);

            foreach ($prepared as $item) {
                $order->items()->create($item);
            }

            $logger->log('created', 'customer_orders', "Bon de commande {$order->number} cree.", $order, newValues: $order->only(['client_id', 'customer_reference', 'total']));

            return $order;
            });
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        return redirect()->route('customer-orders.show', $order)->with('success', 'Bon de commande client cree.');
    }

    public function show(CustomerOrder $customerOrder): View
    {
        $customerOrder->load(['client', 'deliverySite', 'proforma', 'items.product', 'deliveryNotes', 'invoices']);

        return view('customer-orders.show', compact('customerOrder'));
    }

    public function attachment(CustomerOrder $customerOrder): Response
    {
        abort_if(! $customerOrder->attachment_path, 404);

        $disk = Storage::disk('local')->exists($customerOrder->attachment_path)
            ? Storage::disk('local')
            : (Storage::disk('public')->exists($customerOrder->attachment_path) ? Storage::disk('public') : null);

        abort_if($disk === null, 404);

        return response()->file($disk->path($customerOrder->attachment_path), [
            'Content-Disposition' => 'inline; filename="'.basename($customerOrder->attachment_path).'"',
        ]);
    }

    public function convertToDeliveryNote(CustomerOrder $customerOrder, Request $request, ConvertCustomerOrderToDeliveryNoteAction $action): RedirectResponse
    {
        try {
            $deliveryNote = $action->execute($customerOrder, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('delivery-notes.show', $deliveryNote)->with('success', 'BL cree depuis le bon de commande client.');
    }

    public function convertToInvoice(CustomerOrder $customerOrder, Request $request, CreateInvoiceFromCommercialSourceAction $action): RedirectResponse
    {
        try {
            $invoice = $action->fromCustomerOrder($customerOrder, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture creee depuis le bon de commande client. Aucun paiement automatique.');
    }
}
