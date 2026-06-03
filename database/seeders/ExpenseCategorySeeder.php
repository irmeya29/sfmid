<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salaire', 'is_sensitive' => true],
            ['name' => 'Loyer', 'is_sensitive' => false],
            ['name' => 'Carburant', 'is_sensitive' => false],
            ['name' => 'Transport', 'is_sensitive' => false],
            ['name' => 'Internet', 'is_sensitive' => false],
            ['name' => 'Communication', 'is_sensitive' => false],
            ['name' => 'Mission', 'is_sensitive' => false],
            ['name' => 'Frais bancaires', 'is_sensitive' => false],
            ['name' => 'Douane', 'is_sensitive' => false],
            ['name' => 'Transit', 'is_sensitive' => false],
            ['name' => 'Maintenance véhicule', 'is_sensitive' => false],
            ['name' => 'Fournitures', 'is_sensitive' => false],
            ['name' => 'Autres charges', 'is_sensitive' => false],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => null,
                    'is_sensitive' => $category['is_sensitive'],
                    'is_active' => true,
                ]
            );
        }
    }
}
