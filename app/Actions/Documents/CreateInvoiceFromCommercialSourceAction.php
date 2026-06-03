<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ValidationAction;
use App\Models\CustomerOrder;
use App\Models\Invoice;
use App\Models\Proforma;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateInvoiceFromCommercialSourceAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function fromProforma(Proforma $proforma, User $user): Invoice
    {
        return $this->execute($proforma, $user);
    }

    public function fromCustomerOrder(CustomerOrder $customerOrder, User $user): Invoice
    {
        return $this->execute($customerOrder, $user);
    }

    private function execute(Model $source, User $user): Invoice
    {
        return DB::transaction(function () use ($source, $user): Invoice {
            $source = $source instanceof Proforma
                ? Proforma::query()->with(['items', 'client'])->whereKey($source->id)->lockForUpdate()->firstOrFail()
                : CustomerOrder::query()->with(['items', 'client'])->whereKey($source->id)->lockForUpdate()->firstOrFail();

            if ($source->status !== DocumentStatus::Validated) {
                throw new RuntimeException('Le document source doit etre valide/confirme avant facturation.');
            }

            $alreadyInvoiced = Invoice::query()
                ->when($source instanceof Proforma, fn ($query) => $query->where('proforma_id', $source->id))
                ->when($source instanceof CustomerOrder, fn ($query) => $query->where('customer_order_id', $source->id))
                ->exists();

            if ($alreadyInvoiced) {
                throw new RuntimeException('Ce document possede deja une facture.');
            }

            $status = $user->bypassesDocumentValidation() ? InvoiceStatus::Validated : InvoiceStatus::Draft;

            $invoice = Invoice::query()->create([
                'number' => $this->documentNumberGenerator->generate('invoice'),
                'delivery_note_id' => null,
                'proforma_id' => $source instanceof Proforma ? $source->id : $source->proforma_id,
                'customer_order_id' => $source instanceof CustomerOrder ? $source->id : null,
                'client_id' => $source->client_id,
                'status' => $status,
                'submitted_at' => $status === InvoiceStatus::Validated ? now() : null,
                'validated_by' => $status === InvoiceStatus::Validated ? $user->id : null,
                'validated_at' => $status === InvoiceStatus::Validated ? now() : null,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $source->subtotal,
                'discount_total' => $source->discount_total,
                'tax_total' => $source->tax_total,
                'total' => $source->total,
                'paid_amount' => 0,
                'balance_due' => $source->total,
                'payment_terms' => $source instanceof Proforma ? ($source->payment_terms ?? $source->terms) : $source->confirmed_terms,
                'notes' => $source->notes ?? null,
                'created_by' => $user->id,
            ]);

            /** @var Collection<int, \App\Models\ProformaItem|\App\Models\CustomerOrderItem> $items */
            $items = $source->items;
            foreach ($items as $item) {
                $invoice->items()->create([
                    'delivery_note_item_id' => null,
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code,
                    'product_internal_reference' => $item->product_internal_reference,
                    'client_product_reference' => $item->client_product_reference,
                    'product_name' => $item->product_name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'line_total' => $item->line_total_ttc ?? $item->line_total,
                ]);
            }

            if ($status === InvoiceStatus::Validated) {
                $this->validationHistoryLogger->log(
                    document: $invoice,
                    action: ValidationAction::Validate,
                    fromStatus: InvoiceStatus::Draft->value,
                    toStatus: InvoiceStatus::Validated->value,
                    comment: 'Validation automatique administrateur.'
                );
            }

            $this->activityLogger->log(
                action: 'created_from_commercial_source',
                module: 'invoices',
                description: "Facture {$invoice->number} creee depuis {$source->number}. Aucun paiement automatique.",
                subject: $invoice,
                newValues: [
                    'proforma_id' => $invoice->proforma_id,
                    'customer_order_id' => $invoice->customer_order_id,
                    'status' => $status->value,
                    'total' => $invoice->total,
                    'balance_due' => $invoice->balance_due,
                ]
            );

            return $invoice->refresh()->load(['client', 'items']);
        });
    }
}
