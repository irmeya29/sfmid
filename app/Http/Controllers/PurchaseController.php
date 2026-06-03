<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierInvoiceRequest;
use App\Http\Requests\StoreSupplierPaymentRequest;
use App\Http\Requests\StoreSupplierPurchaseOrderRequest;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\SupplierPurchaseOrder;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        return view('purchases.index', [
            'requests' => PurchaseRequest::query()->with('supplier')->latest()->limit(10)->get(),
            'orders' => SupplierPurchaseOrder::query()->with('supplier')->latest()->paginate(10),
            'invoices' => SupplierInvoice::query()->with('supplier')->where('balance_due', '>', 0)->latest()->limit(10)->get(),
        ]);
    }

    public function createOrder(): View
    {
        return view('purchases.create-order', [
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
            'products' => Product::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function createRequest(): View
    {
        return view('purchases.create-request', [
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function storeRequest(Request $request, DocumentNumberGenerator $numbers, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'request_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => $numbers->generate('purchase_request'),
            'supplier_id' => $data['supplier_id'] ?? null,
            'status' => 'requested',
            'request_date' => $data['request_date'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $logger->log('created', 'purchases', "Demande d'achat {$purchaseRequest->number} créée.", $purchaseRequest, newValues: $purchaseRequest->toArray());

        return redirect()->route('purchases.index')->with('success', "Demande d'achat créée.");
    }

    public function storeOrder(StoreSupplierPurchaseOrderRequest $request, DocumentNumberGenerator $numbers, ActivityLogger $logger): RedirectResponse
    {
        $order = DB::transaction(function () use ($request, $numbers, $logger): SupplierPurchaseOrder {
            $subtotal = 0;
            $items = [];
            foreach ($request->validated('items') as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $subtotal += $lineTotal;
                $items[] = ['product' => $product, 'data' => $item, 'line_total' => $lineTotal];
            }

            $taxRate = (float) (CompanySetting::query()->where('key', 'sales.default_tax_rate')->value('value') ?? 0);
            $taxTotal = round($subtotal * $taxRate / 100, 2);
            $order = SupplierPurchaseOrder::query()->create([
                'number' => $numbers->generate('supplier_purchase_order'),
                'supplier_id' => $request->validated('supplier_id'),
                'status' => 'ordered',
                'order_date' => $request->validated('order_date'),
                'expected_delivery_date' => $request->validated('expected_delivery_date'),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'terms' => $request->validated('terms'),
                'notes' => $request->validated('notes'),
                'created_by' => $request->user()->id,
            ]);

            foreach ($items as $row) {
                $order->items()->create([
                    'product_id' => $row['product']->id,
                    'product_code' => $row['product']->code,
                    'product_name' => $row['product']->name,
                    'unit' => $row['product']->unit,
                    'quantity' => $row['data']['quantity'],
                    'unit_price' => $row['data']['unit_price'],
                    'line_total' => $row['line_total'],
                ]);
            }

            $logger->log('created', 'purchases', "Bon de commande fournisseur {$order->number} créé.", $order, newValues: $order->toArray());

            return $order;
        });

        return redirect()->route('purchases.orders.show', $order)->with('success', 'Bon de commande fournisseur créé.');
    }

    public function showOrder(SupplierPurchaseOrder $order): View
    {
        $order->load(['supplier', 'items.product', 'invoice.payments']);

        return view('purchases.show-order', compact('order'));
    }

    public function orderPdf(SupplierPurchaseOrder $order): Response
    {
        $order->load(['supplier', 'items.product']);

        return Pdf::loadView('purchases.order-pdf', [
            'order' => $order,
            'company' => CompanySetting::query()->pluck('value', 'key')->all(),
        ])->setPaper('a4')->stream($order->number.'.pdf');
    }

    public function createInvoice(Request $request): View
    {
        return view('purchases.create-invoice', [
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
            'orders' => SupplierPurchaseOrder::query()->with('supplier')->doesntHave('invoice')->latest()->get(),
            'selectedOrderId' => $request->integer('order_id') ?: null,
        ]);
    }

    public function storeInvoice(StoreSupplierInvoiceRequest $request, DocumentNumberGenerator $numbers, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $invoice = SupplierInvoice::query()->create([
            ...$data,
            'number' => $numbers->generate('supplier_invoice'),
            'status' => 'unpaid',
            'paid_amount' => 0,
            'balance_due' => $data['total'],
            'created_by' => $request->user()->id,
        ]);
        if ($invoice->purchaseOrder) {
            $invoice->purchaseOrder->forceFill(['status' => 'invoiced'])->save();
        }
        $logger->log('created', 'purchases', "Facture fournisseur {$invoice->number} enregistrée.", $invoice, newValues: $invoice->toArray());

        return redirect()->route('suppliers.show', $invoice->supplier)->with('success', 'Facture fournisseur enregistrée.');
    }

    public function createPayment(Request $request): View
    {
        return view('purchases.create-payment', [
            'invoices' => SupplierInvoice::query()->with('supplier')->where('balance_due', '>', 0)->latest()->get(),
            'selectedInvoiceId' => $request->integer('supplier_invoice_id') ?: null,
        ]);
    }

    public function storePayment(StoreSupplierPaymentRequest $request, DocumentNumberGenerator $numbers, ActivityLogger $logger): RedirectResponse
    {
        try {
            $payment = DB::transaction(function () use ($request, $numbers, $logger): SupplierPayment {
            $invoice = SupplierInvoice::query()->whereKey($request->validated('supplier_invoice_id'))->lockForUpdate()->firstOrFail();
            $amount = (float) $request->validated('amount');
            if ($amount > (float) $invoice->balance_due) {
                throw new RuntimeException('Le règlement dépasse la dette fournisseur.');
            }

            $payment = SupplierPayment::query()->create([
                'number' => $numbers->generate('supplier_payment'),
                'supplier_id' => $invoice->supplier_id,
                'supplier_invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_date' => $request->validated('payment_date'),
                'method' => $request->validated('method'),
                'reference' => $request->validated('reference'),
                'notes' => $request->validated('notes'),
                'created_by' => $request->user()->id,
            ]);

            $paid = (float) $invoice->paid_amount + $amount;
            $balance = max(0, (float) $invoice->total - $paid);
            $invoice->forceFill(['paid_amount' => $paid, 'balance_due' => $balance, 'status' => $balance <= 0 ? 'paid' : 'partially_paid'])->save();
            $logger->log('created', 'purchases', "Règlement fournisseur {$payment->number} enregistré.", $payment, newValues: $payment->toArray());

            return $payment;
            });
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        return redirect()->route('suppliers.show', $payment->supplier)->with('success', 'Règlement fournisseur enregistré.');
    }
}
