<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'number',
        'delivery_note_id',
        'proforma_id',
        'customer_order_id',
        'client_id',
        'direct_stock_enabled',
        'stock_site_id',
        'stock_moved_by',
        'stock_moved_at',
        'status',
        'issue_date',
        'due_date',
        'subject',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'paid_amount',
        'balance_due',
        'payment_terms',
        'delivery_delay',
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
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'direct_stock_enabled' => 'boolean',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'stock_moved_at' => 'datetime',
        ];
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
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

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

    public function stockSite(): BelongsTo
    {
        return $this->belongsTo(StockSite::class);
    }

    public function stockMover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stock_moved_by');
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

    public function scopePendingValidation(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::PendingValidation->value);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InvoiceStatus::Validated->value,
            InvoiceStatus::Unpaid->value,
            InvoiceStatus::PartiallyPaid->value,
        ])->where('balance_due', '>', 0);
    }

    public function isPayable(): bool
    {
        return $this->status->isPayable() && (float) $this->balance_due > 0;
    }

    public function isFullyPaid(): bool
    {
        return (float) $this->balance_due <= 0;
    }
}
