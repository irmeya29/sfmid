<?php

namespace App\Actions\Documents;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncInvoiceFromProformaAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(Invoice $invoice, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice = Invoice::query()
                ->with(['items', 'proforma.items'])
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invoice->proforma_id || $invoice->delivery_note_id || $invoice->customer_order_id) {
                throw new RuntimeException('Cette facture ne provient pas directement d’une proforma.');
            }

            if (! in_array($invoice->status, [
                InvoiceStatus::Draft,
                InvoiceStatus::Rejected,
                InvoiceStatus::Corrected,
            ], true)) {
                throw new RuntimeException('Seules les factures brouillon, rejetées ou corrigées peuvent être synchronisées.');
            }

            if ($invoice->payments()->exists() || (float) $invoice->paid_amount > 0) {
                throw new RuntimeException('Cette facture possède déjà un paiement et ne peut plus être synchronisée.');
            }

            $proforma = $invoice->proforma;
            if (! $proforma) {
                throw new RuntimeException('Proforma source introuvable.');
            }

            $oldValues = $invoice->only([
                'client_id',
                'subject',
                'currency',
                'subtotal',
                'discount_total',
                'tax_total',
                'total',
                'balance_due',
                'payment_terms',
                'delivery_delay',
                'notes',
            ]);

            $invoice->forceFill([
                'client_id' => $proforma->client_id,
                'subject' => $proforma->subject,
                'currency' => $proforma->currency ?: 'FCFA',
                'subtotal' => $proforma->subtotal,
                'discount_total' => $proforma->discount_total,
                'tax_total' => $proforma->tax_total,
                'total' => $proforma->total,
                'paid_amount' => 0,
                'balance_due' => $proforma->total,
                'payment_terms' => $proforma->payment_terms ?? $proforma->terms,
                'delivery_delay' => $proforma->delivery_delay,
                'notes' => $proforma->notes,
            ])->save();

            $invoice->items()->delete();

            foreach ($proforma->items as $item) {
                $invoice->items()->create([
                    'delivery_note_item_id' => null,
                    'item_type' => $item->item_type ?? 'product',
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code,
                    'product_internal_reference' => $item->product_internal_reference,
                    'client_product_reference' => $item->client_product_reference,
                    'product_name' => $item->product_name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => $item->line_subtotal,
                    'discount_amount' => $item->discount_amount,
                    'tax_rate' => $item->tax_rate ?? 0,
                    'tax_amount' => $item->tax_amount ?? 0,
                    'line_total_ht' => $item->line_total_ht,
                    'line_total_ttc' => $item->line_total_ttc ?? $item->line_total,
                    'line_total' => $item->line_total_ttc ?? $item->line_total,
                ]);
            }

            $this->activityLogger->log(
                action: 'synced_from_proforma',
                module: 'invoices',
                description: "Facture {$invoice->number} synchronisée depuis la proforma {$proforma->number}.",
                subject: $invoice,
                oldValues: $oldValues,
                newValues: $invoice->fresh()->only([
                    'client_id',
                    'subject',
                    'currency',
                    'subtotal',
                    'discount_total',
                    'tax_total',
                    'total',
                    'balance_due',
                    'payment_terms',
                    'delivery_delay',
                    'notes',
                ]),
            );

            return $invoice->refresh()->load(['client', 'proforma', 'items.product']);
        });
    }
}
