<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Hydraulique',
            'Anti-incendie',
            'Lubrification',
            'Pneumatique / instrumentation',
            'Ravitaillement',
            'Environnement',
            'Outillage interne',
            'Divers',
        ];

        foreach ($categories as $name) {
            ProductCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'parent_id' => null,
                    'name' => $name,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
