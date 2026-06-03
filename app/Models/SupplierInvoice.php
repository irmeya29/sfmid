<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['number', 'supplier_invoice_number', 'supplier_id', 'supplier_purchase_order_id', 'status', 'invoice_date', 'due_date', 'total', 'paid_amount', 'balance_due', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['invoice_date' => 'date', 'due_date' => 'date', 'total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'balance_due' => 'decimal:2'];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(SupplierPurchaseOrder::class, 'supplier_purchase_order_id'); }
    public function payments(): HasMany { return $this->hasMany(SupplierPayment::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
