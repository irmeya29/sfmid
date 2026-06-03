<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $total = 100000;

        return [
            'number' => 'FAC-'.fake()->unique()->numberBetween(10000, 99999),
            'delivery_note_id' => null,
            'client_id' => Client::factory(),
            'status' => InvoiceStatus::Draft,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $total,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'balance_due' => $total,
            'payment_terms' => null,
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

    public function validated(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Validated,
            'validated_at' => now(),
        ]);
    }
}
