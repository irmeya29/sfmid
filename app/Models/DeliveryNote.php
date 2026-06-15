<?php

namespace App\Models;

use App\Enums\DeliveryNoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryNote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'number',
        'proforma_id',
        'customer_order_id',
        'client_id',
        'client_delivery_site_id',
        'stock_site_id',
        'status',
        'planned_delivery_date',
        'subject',
        'delivered_at',
        'delivered_by',
        'receiver_name',
        'receiver_phone',
        'delivery_address',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'notes',
        'created_by',
        'submitted_at',
        'validated_by',
        'validated_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'stock_moved_by',
        'stock_moved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryNoteStatus::class,
            'planned_delivery_date' => 'date',
            'delivered_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'stock_moved_at' => 'datetime',
        ];
    }

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function deliverySite(): BelongsTo
    {
        return $this->belongsTo(ClientDeliverySite::class, 'client_delivery_site_id');
    }

    public function stockSite(): BelongsTo
    {
        return $this->belongsTo(StockSite::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function stockSuspenses(): HasMany
    {
        return $this->hasMany(StockSuspense::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    public function validationHistories(): MorphMany
    {
        return $this->morphMany(ValidationHistory::class, 'document');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function stockMover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stock_moved_by');
    }

    public function scopePendingValidation(Builder $query): Builder
    {
        return $query->where('status', DeliveryNoteStatus::PendingValidation->value);
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', DeliveryNoteStatus::Delivered->value);
    }

    public function hasStockAlreadyMoved(): bool
    {
        return $this->stock_moved_at !== null;
    }

    public function canMoveStock(): bool
    {
        return $this->status->canMoveStock() && ! $this->hasStockAlreadyMoved();
    }

    public function canBeInvoiced(): bool
    {
        return $this->status->canBeInvoiced() && $this->invoice === null;
    }
}
