<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            ProductCategorySeeder::class,
            ExpenseCategorySeeder::class,
            PaymentModeSeeder::class,
            MeasurementUnitSeeder::class,
            DocumentNumberSequenceSeeder::class,
            CompanySettingSeeder::class,
        ]);
    }
}
