<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proforma_id',
        'product_id',
        'product_code',
        'product_internal_reference',
        'client_product_reference',
        'product_name',
        'unit',
        'quantity',
        'unit_price',
        'line_subtotal',
        'discount_rate',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'line_total_ht',
        'line_total_ttc',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'discount_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total_ht' => 'decimal:2',
            'line_total_ttc' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
