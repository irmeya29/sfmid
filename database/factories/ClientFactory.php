<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'CLI-'.fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->company(),
            'type' => fake()->randomElement(ClientType::values()),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'ifu' => fake()->optional()->numerify('#######A'),
            'rccm' => fake()->optional()->bothify('RCCM-BF-###-####'),
            'payment_delay_days' => fake()->randomElement([0, 15, 30, 45]),
            'commercial_terms' => null,
            'status' => ClientStatus::Active,
            'created_by' => User::factory(),
        ];
    }
}
