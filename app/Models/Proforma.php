<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proforma extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'number',
        'client_id',
        'client_delivery_site_id',
        'status',
        'issue_date',
        'valid_until',
        'incoterm',
        'currency',
        'payment_terms',
        'delivery_delay',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'terms',
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
        'converted_to_delivery_note_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'converted_to_delivery_note_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function deliverySite(): BelongsTo
    {
        return $this->belongsTo(ClientDeliverySite::class, 'client_delivery_site_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaItem::class);
    }

    public function deliveryNote(): HasOne
    {
        return $this->hasOne(DeliveryNote::class);
    }

    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function validationHistories(): MorphMany
    {
        return $this->morphMany(ValidationHistory::class, 'document');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopePendingValidation(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::PendingValidation->value);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::Validated->value);
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function canBeConvertedToDeliveryNote(): bool
    {
        return $this->status === DocumentStatus::Validated;
    }
}
