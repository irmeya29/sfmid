<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientDeliverySiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->city().' site',
            'address' => fake()->address(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => [
            'is_default' => true,
        ]);
    }
}
