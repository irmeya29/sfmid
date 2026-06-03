<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'DEP-'.fake()->unique()->numberBetween(10000, 99999),
            'expense_category_id' => ExpenseCategory::factory(),
            'status' => ExpenseStatus::Draft,
            'amount' => fake()->randomFloat(2, 1000, 250000),
            'expense_date' => now()->toDateString(),
            'beneficiary' => fake()->name(),
            'payment_method' => 'cash',
            'payment_reference' => null,
            'attachment_path' => null,
            'description' => fake()->sentence(),
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
}
