<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ValidationAction;
use App\Models\ClientProductPrice;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaveProformaAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user, ?Proforma $proforma = null): Proforma
    {
        return DB::transaction(function () use ($data, $user, $proforma): Proforma {
            $isUpdate = $proforma instanceof Proforma;

            if ($isUpdate) {
                $proforma = Proforma::query()
                    ->with('items')
                    ->whereKey($proforma->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $proforma->status->isEditable()) {
                    throw new RuntimeException('Cette proforma ne peut plus être modifiée.');
                }

                $oldValues = $proforma->only([
                    'client_id',
                    'client_delivery_site_id',
                    'status',
                    'issue_date',
                    'valid_until',
                    'incoterm',
                    'currency',
                    'payment_terms',
                    'delivery_delay',
                    'subtotal',
                    'discount_total',
                    'tax_total',
                    'total',
                    'terms',
                    'notes',
                ]);

                $fromStatus = $proforma->status;
            } else {
                $oldValues = null;
                $fromStatus = null;

                $proforma = new Proforma([
                    'number' => $this->documentNumberGenerator->generate('proforma'),
                    'status' => DocumentStatus::Draft,
                    'created_by' => $user->id,
                ]);
            }

            $totals = $this->buildItemsAndTotals($data['items'], (int) $data['client_id']);

            $newStatus = $user->bypassesDocumentValidation()
                ? DocumentStatus::Validated
                : ($isUpdate && $fromStatus === DocumentStatus::Rejected
                    ? DocumentStatus::Corrected
                    : $proforma->status);

            $proforma->fill([
                'client_id' => $data['client_id'],
                'client_delivery_site_id' => $data['client_delivery_site_id'] ?? null,
                'status' => $newStatus,
                'submitted_at' => $newStatus === DocumentStatus::Validated ? now() : $proforma->submitted_at,
                'validated_by' => $newStatus === DocumentStatus::Validated ? $user->id : $proforma->validated_by,
                'validated_at' => $newStatus === DocumentStatus::Validated ? now() : $proforma->validated_at,
                'issue_date' => $data['issue_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'incoterm' => $data['incoterm'] ?? null,
                'currency' => $data['currency'] ?? 'FCFA',
                'payment_terms' => $data['payment_terms'] ?? null,
                'delivery_delay' => $data['delivery_delay'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $proforma->save();

            if ($isUpdate) {
                $proforma->items()->delete();
            }

            foreach ($totals['items'] as $item) {
                $proforma->items()->create($item);
            }

            if ($isUpdate && $fromStatus === DocumentStatus::Rejected) {
                $this->validationHistoryLogger->log(
                    document: $proforma,
                    action: ValidationAction::Correct,
                    fromStatus: DocumentStatus::Rejected->value,
                    toStatus: DocumentStatus::Corrected->value,
                    comment: 'Proforma corrigée après rejet.'
                );
            }

            if ($newStatus === DocumentStatus::Validated && $fromStatus !== DocumentStatus::Validated) {
                $this->validationHistoryLogger->log(
                    document: $proforma,
                    action: ValidationAction::Validate,
                    fromStatus: $fromStatus?->value ?? DocumentStatus::Draft->value,
                    toStatus: DocumentStatus::Validated->value,
                    comment: 'Validation automatique administrateur.'
                );
            }

            $this->activityLogger->log(
                action: $isUpdate ? 'updated' : 'created',
                module: 'proformas',
                description: $isUpdate
                    ? "Proforma {$proforma->number} modifiée."
                    : "Proforma {$proforma->number} créée.",
                subject: $proforma,
                oldValues: $oldValues,
                newValues: $proforma->fresh()->only([
                    'client_id',
                    'client_delivery_site_id',
                    'status',
                    'issue_date',
                    'valid_until',
                    'incoterm',
                    'currency',
                    'payment_terms',
                    'delivery_delay',
                    'subtotal',
                    'discount_total',
                    'tax_total',
                    'total',
                    'terms',
                    'notes',
                ]),
            );

            return $proforma->refresh()->load(['client', 'items.product']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, discount_total: float, tax_total: float, total: float, items: array<int, array<string, mixed>>}
     */
    private function buildItemsAndTotals(array $items, int $clientId): array
    {
        $preparedItems = [];
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $product = Product::query()
                ->whereKey($item['product_id'])
                ->firstOrFail();
            $clientReference = ClientProductPrice::query()
                ->where('client_id', $clientId)
                ->where('product_id', $product->id)
                ->first();

            $quantity = (float) $item['quantity'];
            $unitPrice = isset($item['unit_price']) && (float) $item['unit_price'] > 0
                ? (float) $item['unit_price']
                : (float) $product->sale_price;

            $discountRate = isset($item['discount_rate']) ? (float) $item['discount_rate'] : 0;
            $taxRate = isset($item['tax_rate']) ? (float) $item['tax_rate'] : 0;

            if ($quantity <= 0) {
                throw new RuntimeException("Quantité invalide pour {$product->name}.");
            }

            if ($discountRate < 0 || $discountRate > 100) {
                throw new RuntimeException("Remise invalide pour {$product->name}.");
            }

            if ($taxRate < 0 || $taxRate > 100) {
                throw new RuntimeException("TVA invalide pour {$product->name}.");
            }

            $lineSubtotal = $quantity * $unitPrice;
            $discountAmount = round($lineSubtotal * ($discountRate / 100), 2);
            $lineTotalHt = round($lineSubtotal - $discountAmount, 2);
            $taxAmount = round($lineTotalHt * ($taxRate / 100), 2);
            $lineTotalTtc = round($lineTotalHt + $taxAmount, 2);

            $subtotal += $lineSubtotal;
            $discountTotal += $discountAmount;
            $taxTotal += $taxAmount;

            $preparedItems[] = [
                'product_id' => $product->id,
                'product_code' => $product->code,
                'product_internal_reference' => $product->internal_reference,
                'client_product_reference' => $clientReference?->client_reference,
                'product_name' => $clientReference?->client_designation ?: $product->name,
                'unit' => $product->unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineSubtotal,
                'discount_rate' => $discountRate,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'line_total_ht' => $lineTotalHt,
                'line_total_ttc' => $lineTotalTtc,
                'line_total' => $lineTotalTtc,
            ];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => round($subtotal - $discountTotal + $taxTotal, 2),
            'items' => $preparedItems,
        ];
    }
}
