<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'supplier_id', 'supplier_invoice_id', 'amount', 'payment_date', 'method', 'reference', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
