<?php

namespace Tests\Feature\Validations;

use App\Enums\DocumentStatus;
use App\Enums\ExpenseStatus;
use App\Enums\StockMovementDirection;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\ValidationHistory;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_center_lists_pending_documents_with_filters(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = $this->userWithPermissions(['validations.view']);
        $creator = User::factory()->create(['name' => 'Createur Test']);

        $proforma = Proforma::factory()->pendingValidation()->create([
            'number' => 'PRO-CENTER-001',
            'created_by' => $creator->id,
            'submitted_at' => now(),
        ]);

        Expense::factory()->create([
            'number' => 'DEP-CENTER-001',
            'status' => ExpenseStatus::PendingValidation,
            'submitted_at' => now(),
        ]);

        $this->actingAs($actor)
            ->get(route('validations.index', [
                'type' => 'proforma',
                'creator_id' => $creator->id,
                'date_from' => now()->subDay()->toDateString(),
                'date_to' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($proforma->number)
            ->assertDontSee('DEP-CENTER-001');
    }

    public function test_quick_validation_updates_document_and_history(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = $this->userWithPermissions([
            'validations.view',
            'validations.validate',
            'proformas.validate',
        ]);

        $proforma = Proforma::factory()->pendingValidation()->create([
            'created_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($actor)
            ->post(route('validations.validate', ['proforma', $proforma->id]))
            ->assertRedirect();

        $this->assertSame(DocumentStatus::Validated, $proforma->fresh()->status);
        $this->assertDatabaseHas(ValidationHistory::class, [
            'document_type' => Proforma::class,
            'document_id' => $proforma->id,
            'action' => 'validate',
        ]);
    }

    public function test_rejection_requires_reason_and_rejects_document(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = $this->userWithPermissions([
            'validations.view',
            'validations.reject',
            'expenses.reject',
        ]);

        $expense = Expense::factory()->create([
            'expense_category_id' => ExpenseCategory::factory()->create(['is_sensitive' => true])->id,
            'status' => ExpenseStatus::PendingValidation,
            'submitted_at' => now(),
            'created_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($actor)
            ->from(route('validations.index'))
            ->post(route('validations.reject', ['expense', $expense->id]), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($actor)
            ->post(route('validations.reject', ['expense', $expense->id]), ['reason' => 'Justificatif incomplet.'])
            ->assertRedirect();

        $expense->refresh();

        $this->assertSame(ExpenseStatus::Rejected, $expense->status);
        $this->assertSame('Justificatif incomplet.', $expense->rejection_reason);
    }

    public function test_stock_movement_can_be_rejected_from_center(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = $this->userWithPermissions([
            'validations.view',
            'validations.reject',
            'stock.validate_movement',
        ]);

        $movement = StockMovement::query()->create([
            'product_id' => Product::factory()->create()->id,
            'type' => StockMovementType::NegativeAdjustment,
            'direction' => StockMovementDirection::Out,
            'status' => StockMovementStatus::PendingValidation,
            'quantity' => 3,
            'unit_cost' => 1000,
            'physical_before' => 10,
            'physical_after' => 10,
            'reserved_before' => 0,
            'reserved_after' => 0,
            'suspense_before' => 0,
            'suspense_after' => 0,
            'tool_before' => 0,
            'tool_after' => 0,
            'reason' => 'Inventaire.',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($actor)
            ->post(route('validations.reject', ['stock_movement', $movement->id]), [
                'reason' => 'A revoir.',
            ])
            ->assertRedirect();

        $this->assertSame(StockMovementStatus::Rejected, $movement->fresh()->status);
        $this->assertDatabaseHas(ValidationHistory::class, [
            'document_type' => StockMovement::class,
            'document_id' => $movement->id,
            'action' => 'reject',
        ]);
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::query()->whereIn('slug', $slugs)->pluck('id')->all());

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }
}
