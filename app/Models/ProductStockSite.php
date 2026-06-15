<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockSite extends Model
{
    use HasFactory;

    protected $table = 'product_stock_site';

    protected $fillable = [
        'product_id',
        'stock_site_id',
        'physical_stock',
        'reserved_stock',
        'suspense_stock',
        'tool_stock',
    ];

    protected function casts(): array
    {
        return [
            'physical_stock' => 'decimal:3',
            'reserved_stock' => 'decimal:3',
            'suspense_stock' => 'decimal:3',
            'tool_stock' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockSite(): BelongsTo
    {
        return $this->belongsTo(StockSite::class);
    }
}
