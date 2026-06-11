<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProformaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'PRO-'.fake()->unique()->numberBetween(10000, 99999),
            'client_id' => Client::factory(),
            'client_delivery_site_id' => null,
            'status' => DocumentStatus::Draft,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'subject' => 'Fourniture de produits et services',
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'terms' => null,
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
            'converted_to_delivery_note_at' => null,
        ];
    }

    public function pendingValidation(): static
    {
        return $this->state(fn () => [
            'status' => DocumentStatus::PendingValidation,
            'submitted_at' => now(),
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn () => [
            'status' => DocumentStatus::Validated,
            'validated_at' => now(),
        ]);
    }
}
