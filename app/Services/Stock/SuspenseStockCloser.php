<?php

namespace App\Services\Stock;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use RuntimeException;

class SuspenseStockCloser
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly StockSiteInventory $inventory,
    ) {}

    public function closeForInvoice(Invoice $invoice, User $user, ?string $reason = null): void
    {
        foreach ($invoice->stockSuspenses()->where('status', 'open')->with('deliveryNote')->get() as $suspense) {
            $product = Product::query()
                ->whereKey($suspense->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantityToClose = (float) $suspense->quantity - (float) $suspense->closed_quantity;

            if ($quantityToClose <= 0) {
                continue;
            }

            $stockSiteId = (int) ($suspense->stock_site_id ?: $this->inventory->defaultSite()->id);
            $stockRow = $this->inventory->stockRow($product, $stockSiteId, true);

            $physicalBefore = (float) $stockRow->physical_stock;
            $reservedBefore = (float) $stockRow->reserved_stock;
            $suspenseBefore = (float) $stockRow->suspense_stock;
            $toolBefore = (float) $stockRow->tool_stock;

            if ($suspenseBefore < $quantityToClose) {
                throw new RuntimeException("Stock en suspens incoherent pour {$product->code} - {$product->name}.");
            }

            $suspenseAfter = $suspenseBefore - $quantityToClose;

            $stockRow->forceFill([
                'suspense_stock' => $suspenseAfter,
            ])->save();
            $this->inventory->syncProductTotals($product);

            $closingReason = $reason ?: "Facture {$invoice->number} validee.";

            $suspense->forceFill([
                'closed_quantity' => $suspense->quantity,
                'status' => 'closed',
                'closed_at' => now(),
                'closing_reason' => $closingReason,
                'closed_by' => $user->id,
            ])->save();

            $attributes = [
                'product_id' => $product->id,
                'stock_site_id' => $stockSiteId,
                'type' => StockMovementType::SuspenseClose,
                'direction' => StockMovementDirection::CloseSuspense,
                'status' => StockMovementStatus::Validated,
                'quantity' => $quantityToClose,
                'unit_cost' => $product->purchase_price,
                'physical_before' => $physicalBefore,
                'physical_after' => $physicalBefore,
                'reserved_before' => $reservedBefore,
                'reserved_after' => $reservedBefore,
                'suspense_before' => $suspenseBefore,
                'suspense_after' => $suspenseAfter,
                'tool_before' => $toolBefore,
                'tool_after' => $toolBefore,
                'reason' => "Cloture stock en suspens apres validation facture {$invoice->number}.",
                'created_by' => $user->id,
                'validated_by' => $user->id,
                'validated_at' => now(),
            ];

            if ($suspense->deliveryNote) {
                $suspense->deliveryNote->stockMovements()->create($attributes);
            } else {
                StockMovement::query()->create($attributes);
            }

            $this->activityLogger->log(
                action: 'closed_suspense_stock',
                module: 'stock',
                description: "Stock en suspens cloture pour la facture {$invoice->number}.",
                subject: $suspense,
                oldValues: [
                    'status' => 'open',
                    'closed_quantity' => (float) $suspense->getOriginal('closed_quantity'),
                    'product_suspense_stock' => $suspenseBefore,
                ],
                newValues: [
                    'status' => 'closed',
                    'closed_quantity' => (float) $suspense->quantity,
                    'product_suspense_stock' => $suspenseAfter,
                ],
            );
        }
    }
}
