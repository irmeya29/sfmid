<?php

namespace App\Services\Stock;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use RuntimeException;

class DirectInvoiceStockMover
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly StockSiteInventory $inventory,
    ) {}

    public function moveForValidatedInvoice(Invoice $invoice, User $user): void
    {
        if (! $invoice->direct_stock_enabled || $invoice->stock_moved_at !== null) {
            return;
        }

        if ($invoice->delivery_note_id !== null) {
            return;
        }

        $stockSiteId = (int) ($invoice->stock_site_id ?: $this->inventory->salesSite()->id);

        $invoice->loadMissing('items');

        foreach ($invoice->items as $item) {
            $product = Product::query()
                ->whereKey($item->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $stockRow = $this->inventory->stockRow($product, $stockSiteId, true);
            $quantity = (float) $item->quantity;

            if ($quantity <= 0) {
                throw new RuntimeException("Quantite invalide pour {$item->product_name}.");
            }

            $physicalBefore = (float) $stockRow->physical_stock;
            $reservedBefore = (float) $stockRow->reserved_stock;
            $suspenseBefore = (float) $stockRow->suspense_stock;
            $toolBefore = (float) $stockRow->tool_stock;
            $physicalAfter = $physicalBefore - $quantity;

            if ($physicalAfter < 0) {
                throw new RuntimeException("Stock physique insuffisant sur le site de vente pour {$product->code} - {$product->name}.");
            }

            $stockRow->forceFill(['physical_stock' => $physicalAfter])->save();
            $this->inventory->syncProductTotals($product);

            $invoice->stockMovements()->create([
                'product_id' => $product->id,
                'stock_site_id' => $stockSiteId,
                'type' => StockMovementType::DeliveryExit,
                'direction' => StockMovementDirection::Out,
                'status' => StockMovementStatus::Validated,
                'quantity' => $quantity,
                'unit_cost' => $product->purchase_price,
                'physical_before' => $physicalBefore,
                'physical_after' => $physicalAfter,
                'reserved_before' => $reservedBefore,
                'reserved_after' => $reservedBefore,
                'suspense_before' => $suspenseBefore,
                'suspense_after' => $suspenseBefore,
                'tool_before' => $toolBefore,
                'tool_after' => $toolBefore,
                'reason' => "Vente directe facture {$invoice->number}.",
                'created_by' => $user->id,
                'validated_by' => $user->id,
                'validated_at' => now(),
            ]);
        }

        $invoice->forceFill([
            'stock_site_id' => $stockSiteId,
            'stock_moved_by' => $user->id,
            'stock_moved_at' => now(),
        ])->save();

        $this->activityLogger->log(
            action: 'direct_invoice_stock_moved',
            module: 'stock',
            description: "Sortie stock appliquee pour la facture directe {$invoice->number}.",
            subject: $invoice,
            newValues: [
                'stock_site_id' => $stockSiteId,
                'stock_moved_by' => $user->id,
                'stock_moved_at' => $invoice->stock_moved_at,
            ],
        );
    }
}
