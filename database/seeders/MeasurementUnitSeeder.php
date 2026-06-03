<?php

namespace Database\Seeders;

use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MeasurementUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Pièce', 'code' => 'piece'],
            ['name' => 'Mètre', 'code' => 'meter'],
            ['name' => 'Litre', 'code' => 'liter'],
            ['name' => 'Kit', 'code' => 'kit'],
            ['name' => 'Ensemble', 'code' => 'set'],
            ['name' => 'Boîte', 'code' => 'box'],
            ['name' => 'Rouleau', 'code' => 'roll'],
            ['name' => 'Paire', 'code' => 'pair'],
            ['name' => 'Carton', 'code' => 'carton'],
        ];

        foreach ($units as $unit) {
            MeasurementUnit::query()->updateOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
