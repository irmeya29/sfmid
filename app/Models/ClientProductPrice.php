<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'product_id',
        'client_reference',
        'client_designation',
        'sale_price',
        'discount_rate',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'discount_rate' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
