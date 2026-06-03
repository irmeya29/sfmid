<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplierPurchaseOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['number', 'purchase_request_id', 'supplier_id', 'status', 'order_date', 'expected_delivery_date', 'subtotal', 'tax_total', 'total', 'terms', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['order_date' => 'date', 'expected_delivery_date' => 'date', 'subtotal' => 'decimal:2', 'tax_total' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseRequest(): BelongsTo { return $this->belongsTo(PurchaseRequest::class); }
    public function items(): HasMany { return $this->hasMany(SupplierPurchaseOrderItem::class); }
    public function invoice(): HasOne { return $this->hasOne(SupplierInvoice::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
