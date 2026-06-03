<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'REC-'.fake()->unique()->numberBetween(10000, 99999),
            'invoice_id' => Invoice::factory(),
            'status' => PaymentStatus::Draft,
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'reference' => fake()->optional()->bothify('REF-####'),
            'attachment_path' => null,
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
        ];
    }

    public function pendingValidation(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::PendingValidation,
            'submitted_at' => now(),
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Validated,
            'validated_at' => now(),
        ]);
    }
}
