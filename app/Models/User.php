<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withTimestamps();
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function validationHistories(): HasMany
    {
        return $this->hasMany(ValidationHistory::class);
    }

    public function createdClients(): HasMany
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    public function createdProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function createdProformas(): HasMany
    {
        return $this->hasMany(Proforma::class, 'created_by');
    }

    public function createdDeliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class, 'created_by');
    }

    public function createdInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function createdPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'created_by');
    }

    public function createdExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasPermission(string $slug): bool
    {
        $this->loadMissing([
            'roles.permissions',
            'permissionOverrides.permission',
        ]);

        if ($this->roles->contains('slug', 'super-admin')) {
            return true;
        }

        $override = $this->permissionOverrides
            ->first(fn (UserPermissionOverride $override): bool => $override->permission?->slug === $slug);

        if ($override !== null) {
            return (bool) $override->is_allowed;
        }

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains('slug', $slug);
    }

    /**
     * @param  array<int, string>  $slugs
     */
    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    public function bypassesDocumentValidation(): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains(fn (Role $role): bool => in_array($role->slug, [
            'admin',
            'super-admin',
        ], true));
    }
}
