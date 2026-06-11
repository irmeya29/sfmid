<?php

namespace App\Actions\Documents;

use App\Enums\DeliveryNoteStatus;
use App\Enums\ValidationAction;
use App\Models\ClientProductPrice;
use App\Models\DeliveryNote;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaveDeliveryNoteAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user, ?DeliveryNote $deliveryNote = null): DeliveryNote
    {
        return DB::transaction(function () use ($data, $user, $deliveryNote): DeliveryNote {
            $isUpdate = $deliveryNote instanceof DeliveryNote;

            if ($isUpdate) {
                $deliveryNote = DeliveryNote::query()
                    ->with('items')
                    ->whereKey($deliveryNote->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $deliveryNote->status->isEditable()
                    && ! (
                        $user->hasPermission('sensitive.update_validated_document')
                        && $deliveryNote->status !== DeliveryNoteStatus::Invoiced
                        && $deliveryNote->status !== DeliveryNoteStatus::Cancelled
                        && ! $deliveryNote->hasStockAlreadyMoved()
                    )
                ) {
                    throw new RuntimeException('Ce BL ne peut plus être modifié.');
                }

                $oldValues = $deliveryNote->only([
                    'client_id',
                    'client_delivery_site_id',
                    'status',
                    'planned_delivery_date',
                    'subject',
                    'subtotal',
                    'discount_total',
                    'tax_total',
                    'total',
                    'notes',
                ]);

                $fromStatus = $deliveryNote->status;
            } else {
                $oldValues = null;
                $fromStatus = null;

                $deliveryNote = new DeliveryNote([
                    'number' => $this->documentNumberGenerator->generate('delivery_note'),
                    'status' => DeliveryNoteStatus::Draft,
                    'created_by' => $user->id,
                ]);
            }

            $totals = $this->buildItemsAndTotals($data['items'], (int) $data['client_id']);
            $newStatus = $user->bypassesDocumentValidation()
                ? DeliveryNoteStatus::Validated
                : ($isUpdate && $fromStatus === DeliveryNoteStatus::Rejected
                    ? DeliveryNoteStatus::Corrected
                    : $deliveryNote->status);

            $deliveryNote->fill([
                'client_id' => $data['client_id'],
                'client_delivery_site_id' => $data['client_delivery_site_id'] ?? null,
                'status' => $newStatus,
                'submitted_at' => $newStatus === DeliveryNoteStatus::Validated ? now() : $deliveryNote->submitted_at,
                'validated_by' => $newStatus === DeliveryNoteStatus::Validated ? $user->id : $deliveryNote->validated_by,
                'validated_at' => $newStatus === DeliveryNoteStatus::Validated ? now() : $deliveryNote->validated_at,
                'planned_delivery_date' => $data['planned_delivery_date'] ?? null,
                'subject' => $data['subject'],
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => 0,
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            $deliveryNote->save();

            if ($isUpdate) {
                $deliveryNote->items()->delete();
            }

            foreach ($totals['items'] as $item) {
                $deliveryNote->items()->create($item);
            }

            if ($isUpdate && $fromStatus === DeliveryNoteStatus::Rejected) {
                $this->validationHistoryLogger->log(
                    document: $deliveryNote,
                    action: ValidationAction::Correct,
                    fromStatus: DeliveryNoteStatus::Rejected->value,
                    toStatus: DeliveryNoteStatus::Corrected->value,
                    comment: 'BL corrigé après rejet.'
                );
            }

            if ($newStatus === DeliveryNoteStatus::Validated && $fromStatus !== DeliveryNoteStatus::Validated) {
                $this->validationHistoryLogger->log(
                    document: $deliveryNote,
                    action: ValidationAction::Validate,
                    fromStatus: $fromStatus?->value ?? DeliveryNoteStatus::Draft->value,
                    toStatus: DeliveryNoteStatus::Validated->value,
                    comment: 'Validation automatique administrateur.'
                );
            }

            $this->activityLogger->log(
                action: $isUpdate ? 'updated' : 'created',
                module: 'delivery_notes',
                description: $isUpdate
                    ? "BL {$deliveryNote->number} modifié."
                    : "BL {$deliveryNote->number} créé.",
                subject: $deliveryNote,
                oldValues: $oldValues,
                newValues: $deliveryNote->fresh()->only([
                    'client_id',
                    'client_delivery_site_id',
                    'status',
                    'planned_delivery_date',
                    'subject',
                    'subtotal',
                    'discount_total',
                    'tax_total',
                    'total',
                    'notes',
                ]),
            );

            return $deliveryNote->refresh()->load(['client', 'items.product']);
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
            $deliveredQuantity = isset($item['delivered_quantity'])
                ? (float) $item['delivered_quantity']
                : $quantity;
            $unitPrice = isset($item['unit_price']) && (float) $item['unit_price'] > 0
                ? (float) $item['unit_price']
                : (float) $product->sale_price;
            $discountAmount = isset($item['discount_amount']) ? (float) $item['discount_amount'] : 0;

            if ($quantity <= 0 || $deliveredQuantity <= 0 || $deliveredQuantity > $quantity) {
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
                'product_id' => $product->id,
                'product_code' => $product->code,
                'product_internal_reference' => $product->internal_reference,
                'client_product_reference' => $clientReference?->client_reference,
                'product_name' => $clientReference?->client_designation ?: $product->name,
                'unit' => $product->unit,
                'quantity' => $quantity,
                'delivered_quantity' => $deliveredQuantity,
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
