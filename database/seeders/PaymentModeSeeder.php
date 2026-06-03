<?php

namespace Database\Seeders;

use App\Models\PaymentMode;
use Illuminate\Database\Seeder;

class PaymentModeSeeder extends Seeder
{
    public function run(): void
    {
        $modes = [
            ['name' => 'Espèces', 'code' => 'cash'],
            ['name' => 'Virement bancaire', 'code' => 'bank_transfer'],
            ['name' => 'Chèque', 'code' => 'check'],
            ['name' => 'Mobile Money', 'code' => 'mobile_money'],
            ['name' => 'Autre', 'code' => 'other'],
        ];

        foreach ($modes as $mode) {
            PaymentMode::query()->updateOrCreate(
                ['code' => $mode['code']],
                [
                    'name' => $mode['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
