<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductStockKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'code',
        'name',
        'brand',
        'internal_reference',
        'supplier_reference',
        'description',
        'unit',
        'purchase_price',
        'sale_price',
        'physical_stock',
        'reserved_stock',
        'suspense_stock',
        'tool_stock',
        'alert_threshold',
        'stock_kind',
        'status',
        'photo_path',
        'technical_sheet_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'physical_stock' => 'decimal:3',
            'reserved_stock' => 'decimal:3',
            'suspense_stock' => 'decimal:3',
            'tool_stock' => 'decimal:3',
            'alert_threshold' => 'decimal:3',
            'stock_kind' => ProductStockKind::class,
            'status' => ProductStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clientPrices(): HasMany
    {
        return $this->hasMany(ClientProductPrice::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockSiteStocks(): HasMany
    {
        return $this->hasMany(ProductStockSite::class);
    }

    public function proformaItems(): HasMany
    {
        return $this->hasMany(ProformaItem::class);
    }

    public function deliveryNoteItems(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockSuspenses(): HasMany
    {
        return $this->hasMany(StockSuspense::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function scopeCommercial(Builder $query): Builder
    {
        return $query->where('stock_kind', ProductStockKind::Commercial->value);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('physical_stock', '<=', 'alert_threshold');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): Builder {
            return $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('internal_reference', 'like', "%{$search}%")
                    ->orWhere('supplier_reference', 'like', "%{$search}%");
            });
        });
    }

    public function availableStock(): float
    {
        return max(0, (float) $this->physical_stock - (float) $this->reserved_stock);
    }

    public function hasEnoughAvailableStock(float $quantity): bool
    {
        return $this->availableStock() >= $quantity;
    }

    public function isLowStock(): bool
    {
        return (float) $this->physical_stock <= (float) $this->alert_threshold;
    }
}
