<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Proforma;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProformaItemFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = 2;
        $unitPrice = (float) $product->sale_price;
        $lineTotal = $quantity * $unitPrice;

        return [
            'proforma_id' => Proforma::factory(),
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_rate' => 0,
            'discount_amount' => 0,
            'line_total' => $lineTotal,
        ];
    }
}
