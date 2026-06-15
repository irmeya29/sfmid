<?php

namespace App\Actions\Stock;

use App\Enums\DeliveryNoteStatus;
use App\Enums\StockMovementDirection;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Models\DeliveryNote;
use App\Models\Product;
use App\Models\StockSuspense;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Stock\StockSiteInventory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MarkDeliveryNoteAsDeliveredAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly StockSiteInventory $inventory,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(
        DeliveryNote $deliveryNote,
        User $user,
        ?string $receiverName = null,
        ?string $receiverPhone = null,
        ?Carbon $deliveredAt = null,
        ?string $deliveryAddress = null,
    ): DeliveryNote
    {
        if (! $user->can('markDelivered', $deliveryNote)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($deliveryNote, $user, $receiverName, $receiverPhone, $deliveredAt, $deliveryAddress): DeliveryNote {
            $deliveredAt ??= now();

            $deliveryNote = DeliveryNote::query()
                ->with(['items'])
                ->whereKey($deliveryNote->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($deliveryNote->status, [
                DeliveryNoteStatus::Validated,
                DeliveryNoteStatus::Prepared,
            ], true)) {
                throw new RuntimeException('Le BL doit être validé ou préparé avant livraison.');
            }

            if ($deliveryNote->stock_moved_at !== null) {
                throw new RuntimeException('Le stock de ce BL a déjà été déplacé.');
            }

            if ($deliveryNote->items->isEmpty()) {
                throw new RuntimeException('Impossible de livrer un BL sans lignes.');
            }

            $stockSiteId = (int) ($deliveryNote->stock_site_id ?: $this->inventory->salesSite()->id);

            foreach ($deliveryNote->items as $item) {
                $quantity = (float) $item->delivered_quantity;

                if ($quantity <= 0) {
                    throw new RuntimeException("Quantité livrée invalide pour {$item->product_name}.");
                }

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $stockRow = $this->inventory->stockRow($product, $stockSiteId, true);

                $physicalBefore = (float) $stockRow->physical_stock;
                $reservedBefore = (float) $stockRow->reserved_stock;
                $suspenseBefore = (float) $stockRow->suspense_stock;
                $toolBefore = (float) $stockRow->tool_stock;

                if ($physicalBefore < $quantity) {
                    throw new RuntimeException("Stock physique insuffisant sur le site selectionne pour {$product->code} - {$product->name}.");
                }

                $physicalAfter = $physicalBefore - $quantity;
                $suspenseAfter = $suspenseBefore + $quantity;

                $stockRow->forceFill([
                    'physical_stock' => $physicalAfter,
                    'suspense_stock' => $suspenseAfter,
                ])->save();
                $this->inventory->syncProductTotals($product);

                $deliveryNote->stockMovements()->create([
                    'product_id' => $product->id,
                    'stock_site_id' => $stockSiteId,
                    'type' => StockMovementType::SuspenseOpen,
                    'direction' => StockMovementDirection::Suspense,
                    'status' => StockMovementStatus::Validated,
                    'quantity' => $quantity,
                    'unit_cost' => $product->purchase_price,
                    'physical_before' => $physicalBefore,
                    'physical_after' => $physicalAfter,
                    'reserved_before' => $reservedBefore,
                    'reserved_after' => $reservedBefore,
                    'suspense_before' => $suspenseBefore,
                    'suspense_after' => $suspenseAfter,
                    'tool_before' => $toolBefore,
                    'tool_after' => $toolBefore,
                    'reason' => "Livraison BL {$deliveryNote->number} : passage stock physique vers stock en suspens.",
                    'created_by' => $user->id,
                    'validated_by' => $user->id,
                    'validated_at' => $deliveredAt,
                ]);

                StockSuspense::query()->create([
                    'client_id' => $deliveryNote->client_id,
                    'product_id' => $product->id,
                    'stock_site_id' => $stockSiteId,
                    'delivery_note_id' => $deliveryNote->id,
                    'delivery_note_item_id' => $item->id,
                    'invoice_id' => null,
                    'quantity' => $quantity,
                    'closed_quantity' => 0,
                    'status' => 'open',
                    'delivered_at' => $deliveredAt,
                    'closed_at' => null,
                    'closing_reason' => null,
                    'created_by' => $user->id,
                    'closed_by' => null,
                ]);
            }

            $deliveryNote->forceFill([
                'status' => DeliveryNoteStatus::Delivered,
                'delivered_at' => $deliveredAt,
                'delivered_by' => $user->id,
                'receiver_name' => $receiverName,
                'receiver_phone' => $receiverPhone,
                'delivery_address' => $deliveryAddress,
                'stock_moved_by' => $user->id,
                'stock_moved_at' => $deliveredAt,
            ])->save();

            $this->activityLogger->log(
                action: 'marked_delivered',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} marqué comme livré. Stock physique transféré vers stock en suspens.",
                subject: $deliveryNote,
                oldValues: [
                    'status' => DeliveryNoteStatus::Validated->value,
                    'stock_moved_at' => null,
                ],
                newValues: [
                    'status' => DeliveryNoteStatus::Delivered->value,
                    'stock_moved_by' => $user->id,
                    'stock_moved_at' => $deliveryNote->stock_moved_at,
                ],
            );

            return $deliveryNote->refresh()->load(['items', 'stockSuspenses', 'stockMovements']);
        });
    }
}
