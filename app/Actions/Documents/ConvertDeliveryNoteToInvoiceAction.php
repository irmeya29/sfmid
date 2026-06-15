<?php

namespace App\Actions\Documents;

use App\Enums\DeliveryNoteStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ValidationAction;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Stock\SuspenseStockCloser;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConvertDeliveryNoteToInvoiceAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
        private readonly SuspenseStockCloser $suspenseStockCloser,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(DeliveryNote $deliveryNote, User $user): Invoice
    {
        if (! $user->can('convertToInvoice', $deliveryNote)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($deliveryNote, $user): Invoice {
            $deliveryNote = DeliveryNote::query()
                ->with(['items', 'invoice', 'stockSuspenses', 'proforma'])
                ->whereKey($deliveryNote->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($deliveryNote->status !== DeliveryNoteStatus::Delivered) {
                throw new RuntimeException('Seul un BL livré peut être converti en facture.');
            }

            if ($deliveryNote->stock_moved_at === null) {
                throw new RuntimeException('Le stock du BL doit être déplacé avant facturation.');
            }

            if ($deliveryNote->invoice !== null) {
                throw new RuntimeException('Ce BL possède déjà une facture.');
            }

            if ($deliveryNote->items->isEmpty()) {
                throw new RuntimeException('Impossible de facturer un BL sans lignes.');
            }

            $invoiceStatus = $user->bypassesDocumentValidation()
                ? InvoiceStatus::Validated
                : InvoiceStatus::Draft;

            $invoice = Invoice::query()->create([
                'number' => $this->documentNumberGenerator->generate('invoice'),
                'delivery_note_id' => $deliveryNote->id,
                'proforma_id' => $deliveryNote->proforma_id,
                'customer_order_id' => $deliveryNote->customer_order_id,
                'client_id' => $deliveryNote->client_id,
                'status' => $invoiceStatus,
                'submitted_at' => $invoiceStatus === InvoiceStatus::Validated ? now() : null,
                'validated_by' => $invoiceStatus === InvoiceStatus::Validated ? $user->id : null,
                'validated_at' => $invoiceStatus === InvoiceStatus::Validated ? now() : null,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subject' => $deliveryNote->subject ?: $deliveryNote->proforma?->subject ?: "Facturation du BL {$deliveryNote->number}",
                'incoterm' => $deliveryNote->proforma?->incoterm,
                'currency' => $deliveryNote->proforma?->currency ?: 'FCFA',
                'subtotal' => $deliveryNote->subtotal,
                'discount_total' => $deliveryNote->discount_total,
                'tax_total' => $deliveryNote->tax_total,
                'total' => $deliveryNote->total,
                'paid_amount' => 0,
                'balance_due' => $deliveryNote->total,
                'payment_terms' => $deliveryNote->proforma?->payment_terms ?? $deliveryNote->proforma?->terms,
                'delivery_delay' => $deliveryNote->proforma?->delivery_delay,
                'notes' => $deliveryNote->notes,
                'created_by' => $user->id,
            ]);

            foreach ($deliveryNote->items as $item) {
                $invoice->items()->create([
                    'delivery_note_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code,
                    'product_internal_reference' => $item->product_internal_reference,
                    'client_product_reference' => $item->client_product_reference,
                    'product_name' => $item->product_name,
                    'unit' => $item->unit,
                    'quantity' => $item->delivered_quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => (float) $item->delivered_quantity * (float) $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'line_total_ht' => $item->line_total,
                    'line_total_ttc' => $item->line_total,
                    'line_total' => $item->line_total,
                ]);
            }

            $deliveryNote->stockSuspenses()->update([
                'invoice_id' => $invoice->id,
                'updated_at' => now(),
            ]);

            $deliveryNote->forceFill([
                'status' => DeliveryNoteStatus::Invoiced,
            ])->save();

            $this->activityLogger->log(
                action: 'converted_to_invoice',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} converti en facture {$invoice->number}.",
                subject: $deliveryNote,
                oldValues: [
                    'status' => DeliveryNoteStatus::Delivered->value,
                ],
                newValues: [
                    'status' => DeliveryNoteStatus::Invoiced->value,
                    'invoice_id' => $invoice->id,
                ],
            );

            $this->activityLogger->log(
                action: 'created_from_delivery_note',
                module: 'invoices',
                description: "Facture {$invoice->number} créée depuis le BL {$deliveryNote->number}. Aucun stock redéduit.",
                subject: $invoice,
                newValues: [
                    'delivery_note_id' => $deliveryNote->id,
                    'status' => $invoiceStatus->value,
                    'total' => $invoice->total,
                    'balance_due' => $invoice->balance_due,
                ],
            );

            if ($invoiceStatus === InvoiceStatus::Validated) {
                $this->suspenseStockCloser->closeForInvoice(
                    $invoice,
                    $user,
                    "Facture {$invoice->number} validee automatiquement."
                );

                $this->validationHistoryLogger->log(
                    document: $invoice,
                    action: ValidationAction::Validate,
                    fromStatus: InvoiceStatus::Draft->value,
                    toStatus: InvoiceStatus::Validated->value,
                    comment: 'Validation automatique administrateur.'
                );
            }

            return $invoice->refresh()->load(['items', 'deliveryNote', 'stockSuspenses']);
        });
    }
}
