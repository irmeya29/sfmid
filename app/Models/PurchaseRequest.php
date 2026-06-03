<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['number', 'supplier_id', 'status', 'request_date', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['request_date' => 'date'];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function purchaseOrder(): HasOne { return $this->hasOne(SupplierPurchaseOrder::class); }
}
