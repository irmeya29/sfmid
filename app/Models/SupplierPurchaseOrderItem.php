<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_purchase_order_id', 'product_id', 'product_code', 'product_name', 'unit', 'quantity', 'unit_price', 'line_total'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(SupplierPurchaseOrder::class, 'supplier_purchase_order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
