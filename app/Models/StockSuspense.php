<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSuspense extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'product_id',
        'stock_site_id',
        'delivery_note_id',
        'delivery_note_item_id',
        'invoice_id',
        'quantity',
        'closed_quantity',
        'status',
        'delivered_at',
        'closed_at',
        'closing_reason',
        'created_by',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'closed_quantity' => 'decimal:3',
            'delivered_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function stockSite(): BelongsTo
    {
        return $this->belongsTo(StockSite::class);
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function deliveryNoteItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryNoteItem::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function remainingQuantity(): float
    {
        return max(0, (float) $this->quantity - (float) $this->closed_quantity);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
