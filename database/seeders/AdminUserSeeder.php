<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@sfmid.local'],
            [
                'name' => 'Administrateur SFMID',
                'phone' => null,
                'password' => Hash::make('Password@123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $role = Role::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $admin->roles()->syncWithoutDetaching([$role->id]);
    }
}
