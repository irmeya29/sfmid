<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_note_id',
        'proforma_item_id',
        'product_id',
        'product_code',
        'product_internal_reference',
        'client_product_reference',
        'product_name',
        'unit',
        'quantity',
        'delivered_quantity',
        'unit_price',
        'discount_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'delivered_quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function proformaItem(): BelongsTo
    {
        return $this->belongsTo(ProformaItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockSuspense(): HasOne
    {
        return $this->hasOne(StockSuspense::class);
    }
}
