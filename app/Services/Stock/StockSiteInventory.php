<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\ProductStockSite;
use App\Models\StockSite;
use RuntimeException;

class StockSiteInventory
{
    public function defaultSite(): StockSite
    {
        return StockSite::query()
            ->where('is_default', true)
            ->first()
            ?? StockSite::query()->firstOrCreate(
                ['code' => 'SITE-PRINCIPAL'],
                [
                    'name' => 'Site principal',
                    'description' => 'Site par defaut cree automatiquement.',
                    'can_store' => true,
                    'can_sell' => true,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
    }

    public function salesSite(): StockSite
    {
        return StockSite::query()
            ->where('is_active', true)
            ->where('can_sell', true)
            ->orderBy('name')
            ->first()
            ?? StockSite::query()->firstOrCreate(
                ['code' => 'VENTE-PRINCIPALE'],
                [
                    'name' => 'Vente principale',
                    'description' => 'Site de vente principal.',
                    'can_store' => true,
                    'can_sell' => true,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );
    }

    public function stockRow(Product $product, StockSite|int|null $site = null, bool $lock = false): ProductStockSite
    {
        $siteId = $site instanceof StockSite
            ? $site->id
            : ($site ?: $this->defaultSite()->id);

        $query = ProductStockSite::query()
            ->where('product_id', $product->id)
            ->where('stock_site_id', $siteId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $row = $query->first();

        if ($row) {
            return $row;
        }

        $hasAnySiteStock = ProductStockSite::query()
            ->where('product_id', $product->id)
            ->exists();

        return ProductStockSite::query()->create([
            'product_id' => $product->id,
            'stock_site_id' => $siteId,
            'physical_stock' => $hasAnySiteStock ? 0 : (float) $product->physical_stock,
            'reserved_stock' => $hasAnySiteStock ? 0 : (float) $product->reserved_stock,
            'suspense_stock' => $hasAnySiteStock ? 0 : (float) $product->suspense_stock,
            'tool_stock' => $hasAnySiteStock ? 0 : (float) $product->tool_stock,
        ]);
    }

    /**
     * @return array{before: float, after: float, row: ProductStockSite}
     */
    public function adjust(Product $product, StockSite|int|null $site, string $stockColumn, float $delta): array
    {
        $row = $this->stockRow($product, $site, true);
        $before = (float) $row->{$stockColumn};
        $after = $before + $delta;

        if ($after < 0) {
            throw new RuntimeException('Stock insuffisant : le mouvement rendrait le stock negatif sur le site selectionne.');
        }

        $row->forceFill([$stockColumn => $after])->save();
        $this->syncProductTotals($product);

        return [
            'before' => $before,
            'after' => $after,
            'row' => $row->refresh(),
        ];
    }

    public function syncProductTotals(Product $product): void
    {
        $totals = ProductStockSite::query()
            ->where('product_id', $product->id)
            ->selectRaw('
                COALESCE(SUM(physical_stock), 0) as physical_stock,
                COALESCE(SUM(reserved_stock), 0) as reserved_stock,
                COALESCE(SUM(suspense_stock), 0) as suspense_stock,
                COALESCE(SUM(tool_stock), 0) as tool_stock
            ')
            ->first();

        $product->forceFill([
            'physical_stock' => (float) $totals->physical_stock,
            'reserved_stock' => (float) $totals->reserved_stock,
            'suspense_stock' => (float) $totals->suspense_stock,
            'tool_stock' => (float) $totals->tool_stock,
        ])->save();
    }
}
