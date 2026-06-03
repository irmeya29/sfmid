<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'phone',
        'email',
        'address',
        'ifu',
        'rccm',
        'payment_delay_days',
        'commercial_terms',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'status' => ClientStatus::class,
            'payment_delay_days' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function deliverySites(): HasMany
    {
        return $this->hasMany(ClientDeliverySite::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ClientProductPrice::class);
    }

    public function proformas(): HasMany
    {
        return $this->hasMany(Proforma::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function stockSuspenses(): HasMany
    {
        return $this->hasMany(StockSuspense::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::Active->value);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): Builder {
            return $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('ifu', 'like', "%{$search}%")
                    ->orWhere('rccm', 'like', "%{$search}%");
            });
        });
    }
}
