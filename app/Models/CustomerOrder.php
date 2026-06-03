<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'number',
        'proforma_id',
        'client_id',
        'client_delivery_site_id',
        'customer_reference',
        'order_date',
        'status',
        'confirmed_terms',
        'attachment_path',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'order_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
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
        return $this->hasMany(CustomerOrderItem::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
