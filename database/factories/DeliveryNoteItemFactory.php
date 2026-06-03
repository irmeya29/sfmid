<?php

namespace Database\Factories;

use App\Models\DeliveryNote;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryNoteItemFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = 2;
        $unitPrice = (float) $product->sale_price;

        return [
            'delivery_note_id' => DeliveryNote::factory(),
            'proforma_item_id' => null,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => $quantity,
            'delivered_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => 0,
            'line_total' => $quantity * $unitPrice,
        ];
    }
}
