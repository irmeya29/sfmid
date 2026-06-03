<?php

namespace App\Models;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'direction',
        'status',
        'quantity',
        'unit_cost',
        'physical_before',
        'physical_after',
        'reserved_before',
        'reserved_after',
        'suspense_before',
        'suspense_after',
        'tool_before',
        'tool_after',
        'source_type',
        'source_id',
        'reason',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'direction' => StockMovementDirection::class,
            'status' => StockMovementStatus::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'physical_before' => 'decimal:3',
            'physical_after' => 'decimal:3',
            'reserved_before' => 'decimal:3',
            'reserved_after' => 'decimal:3',
            'suspense_before' => 'decimal:3',
            'suspense_after' => 'decimal:3',
            'tool_before' => 'decimal:3',
            'tool_after' => 'decimal:3',
            'validated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function validationHistories(): MorphMany
    {
        return $this->morphMany(ValidationHistory::class, 'document');
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', StockMovementStatus::Validated->value);
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }
}
