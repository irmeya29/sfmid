<?php

namespace App\Actions\Documents;

use App\Enums\InvoiceStatus;
use App\Enums\ValidationAction;
use App\Models\ClientProductPrice;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaveDirectInvoiceAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user): Invoice
    {
        return DB::transaction(function () use ($data, $user): Invoice {
            $totals = $this->buildItemsAndTotals($data['items'], (int) $data['client_id']);
            $status = $user->bypassesDocumentValidation()
                ? InvoiceStatus::Validated
                : InvoiceStatus::Draft;

            $invoice = Invoice::query()->create([
                'number' => $this->documentNumberGenerator->generate('invoice'),
                'delivery_note_id' => null,
                'client_id' => $data['client_id'],
                'status' => $status,
                'submitted_at' => $status === InvoiceStatus::Validated ? now() : null,
                'validated_by' => $status === InvoiceStatus::Validated ? $user->id : null,
                'validated_at' => $status === InvoiceStatus::Validated ? now() : null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => 0,
                'total' => $totals['total'],
                'paid_amount' => 0,
                'balance_due' => $totals['total'],
                'payment_terms' => $data['payment_terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($totals['items'] as $item) {
                $invoice->items()->create($item);
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
                action: 'created_direct',
                module: 'invoices',
                description: "Facture directe {$invoice->number} créée.",
                subject: $invoice,
                newValues: $invoice->only([
                    'client_id',
                    'status',
                    'issue_date',
                    'due_date',
                    'subtotal',
                    'discount_total',
                    'tax_total',
                    'total',
                    'balance_due',
                ]),
            );

            return $invoice->refresh()->load(['client', 'items.product']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, discount_total: float, total: float, items: array<int, array<string, mixed>>}
     */
    private function buildItemsAndTotals(array $items, int $clientId): array
    {
        $preparedItems = [];
        $subtotal = 0;
        $discountTotal = 0;

        foreach ($items as $item) {
            $product = Product::query()->whereKey($item['product_id'])->firstOrFail();
            $clientReference = ClientProductPrice::query()
                ->where('client_id', $clientId)
                ->where('product_id', $product->id)
                ->first();
            $quantity = (float) $item['quantity'];
            $unitPrice = isset($item['unit_price']) && (float) $item['unit_price'] > 0
                ? (float) $item['unit_price']
                : (float) $product->sale_price;
            $discountAmount = isset($item['discount_amount']) ? (float) $item['discount_amount'] : 0;

            if ($quantity <= 0) {
                throw new RuntimeException("Quantité invalide pour {$product->name}.");
            }

            if ($discountAmount < 0 || $discountAmount > ($quantity * $unitPrice)) {
                throw new RuntimeException("Remise invalide pour {$product->name}.");
            }

            $lineSubtotal = $quantity * $unitPrice;
            $lineTotal = round($lineSubtotal - $discountAmount, 2);

            $subtotal += $lineSubtotal;
            $discountTotal += $discountAmount;

            $preparedItems[] = [
                'delivery_note_item_id' => null,
                'product_id' => $product->id,
                'product_code' => $product->code,
                'product_internal_reference' => $product->internal_reference,
                'client_product_reference' => $clientReference?->client_reference,
                'product_name' => $clientReference?->client_designation ?: $product->name,
                'unit' => $product->unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total' => round($subtotal - $discountTotal, 2),
            'items' => $preparedItems,
        ];
    }
}
