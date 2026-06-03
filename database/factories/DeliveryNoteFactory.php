<?php

namespace Database\Factories;

use App\Enums\DeliveryNoteStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryNoteFactory extends Factory
{
    public function definition(): array
    {
        $client = Client::factory()->create();

        return [
            'number' => 'BL-'.fake()->unique()->numberBetween(10000, 99999),
            'proforma_id' => null,
            'client_id' => $client->id,
            'client_delivery_site_id' => null,
            'status' => DeliveryNoteStatus::Draft,
            'planned_delivery_date' => now()->addDay()->toDateString(),
            'delivered_at' => null,
            'delivered_by' => null,
            'receiver_name' => null,
            'receiver_phone' => null,
            'delivery_address' => null,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'notes' => null,
            'created_by' => User::factory(),
            'submitted_at' => null,
            'validated_by' => null,
            'validated_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'stock_moved_by' => null,
            'stock_moved_at' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn () => [
            'status' => DeliveryNoteStatus::Validated,
            'validated_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => DeliveryNoteStatus::Delivered,
            'delivered_at' => now(),
            'stock_moved_at' => now(),
        ]);
    }
}
