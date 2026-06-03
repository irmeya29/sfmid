<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'number',
        'expense_category_id',
        'status',
        'amount',
        'expense_date',
        'beneficiary',
        'payment_method',
        'payment_reference',
        'attachment_path',
        'description',
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
            'status' => ExpenseStatus::class,
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
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
        return $query->where('status', ExpenseStatus::PendingValidation->value);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', ExpenseStatus::Validated->value);
    }
}
