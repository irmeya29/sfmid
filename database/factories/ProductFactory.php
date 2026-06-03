<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductStockKind;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'code' => 'PRD-'.fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->words(3, true),
            'brand' => fake()->optional()->company(),
            'supplier_reference' => fake()->optional()->bothify('REF-###-???'),
            'unit' => fake()->randomElement(['piece', 'meter', 'liter', 'kit']),
            'purchase_price' => fake()->randomFloat(2, 1000, 50000),
            'sale_price' => fake()->randomFloat(2, 2000, 80000),
            'physical_stock' => 100,
            'reserved_stock' => 0,
            'suspense_stock' => 0,
            'tool_stock' => 0,
            'alert_threshold' => 10,
            'stock_kind' => ProductStockKind::Commercial,
            'status' => ProductStatus::Active,
            'photo_path' => null,
            'technical_sheet_path' => null,
            'created_by' => User::factory(),
        ];
    }
}
