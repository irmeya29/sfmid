<?php

namespace Tests\Feature\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\DocumentNumberSequence;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_expense_with_attachment_and_validate_it(): void
    {
        Storage::fake('local');
        $this->sequence();

        $creator = $this->userWithPermissions([
            'expenses.view',
            'expenses.create',
            'expenses.update',
            'expenses.submit',
        ]);
        $validator = $this->userWithPermissions([
            'expenses.view',
            'expenses.validate',
            'expenses.export',
        ]);
        $category = ExpenseCategory::factory()->create(['name' => 'Transport']);

        $this->actingAs($creator)->get(route('expenses.create'))->assertOk()->assertSee('Transport');

        $this->actingAs($creator)->post(route('expenses.store'), [
            'expense_category_id' => $category->id,
            'amount' => 15000,
            'expense_date' => now()->toDateString(),
            'beneficiary' => 'Chauffeur',
            'payment_method' => 'cash',
            'payment_reference' => 'BON-1',
            'attachment' => UploadedFile::fake()->image('ticket.jpg'),
            'description' => 'Transport matériel chantier.',
        ])->assertRedirect();

        $expense = Expense::query()->firstOrFail();

        $this->assertSame('DEP-'.now()->format('Y').'-00001', $expense->number);
        $this->assertSame(ExpenseStatus::Draft, $expense->status);
        $this->assertNotNull($expense->attachment_path);
        Storage::disk('local')->assertExists($expense->attachment_path);

        $this->actingAs($creator)->get(route('expenses.attachment', $expense))
            ->assertOk();

        $this->actingAs($creator)->post(route('expenses.submit', $expense))
            ->assertRedirect(route('expenses.show', $expense));
        $this->assertSame(ExpenseStatus::PendingValidation, $expense->refresh()->status);

        $this->actingAs($validator)->post(route('expenses.validate', $expense))
            ->assertRedirect(route('expenses.show', $expense));
        $this->assertSame(ExpenseStatus::Validated, $expense->refresh()->status);

        $this->actingAs($validator)->get(route('expenses.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_expense_can_be_rejected_with_required_reason(): void
    {
        $creator = User::factory()->create();
        $rejector = $this->userWithPermissions(['expenses.view', 'expenses.reject']);
        $expense = Expense::factory()->create([
            'created_by' => $creator->id,
            'status' => ExpenseStatus::PendingValidation,
            'submitted_at' => now(),
        ]);

        $this->actingAs($rejector)->from(route('expenses.show', $expense))->post(route('expenses.reject', $expense), [
            'reason' => '',
        ])->assertRedirect(route('expenses.show', $expense))->assertSessionHasErrors('reason');

        $this->actingAs($rejector)->post(route('expenses.reject', $expense), [
            'reason' => 'Justificatif incomplet.',
        ])->assertRedirect(route('expenses.show', $expense));

        $this->assertSame(ExpenseStatus::Rejected, $expense->refresh()->status);
        $this->assertSame('Justificatif incomplet.', $expense->rejection_reason);
    }

    public function test_draft_expense_can_be_deleted_but_pending_expense_cannot(): void
    {
        Storage::fake('local');

        $user = $this->userWithPermissions(['expenses.view', 'expenses.delete_draft']);
        $draft = Expense::factory()->create([
            'status' => ExpenseStatus::Draft,
            'attachment_path' => 'expense-attachments/ticket.jpg',
        ]);
        $pending = Expense::factory()->create([
            'status' => ExpenseStatus::PendingValidation,
        ]);

        Storage::disk('local')->put($draft->attachment_path, 'ticket');

        $this->actingAs($user)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Supprimer la depense');

        $this->actingAs($user)->delete(route('expenses.destroy', $pending))
            ->assertForbidden();

        $this->actingAs($user)->delete(route('expenses.destroy', $draft))
            ->assertRedirect(route('expenses.index'));

        $this->assertSoftDeleted($draft);
        Storage::disk('local')->assertMissing($draft->attachment_path);
    }

    public function test_sensitive_expense_categories_are_hidden_and_forbidden_without_permission(): void
    {
        $regularUser = $this->userWithPermissions(['expenses.view', 'expenses.create']);
        $sensitiveUser = $this->userWithPermissions(['expenses.view', 'expenses.create', 'expenses.view_sensitive']);
        $normal = ExpenseCategory::factory()->create(['name' => 'Carburant', 'is_sensitive' => false]);
        $salary = ExpenseCategory::factory()->create(['name' => 'Salaires', 'is_sensitive' => true]);

        Expense::factory()->create([
            'expense_category_id' => $normal->id,
            'description' => 'Carburant mission',
        ]);
        Expense::factory()->create([
            'expense_category_id' => $salary->id,
            'description' => 'Salaire direction',
        ]);

        $this->actingAs($regularUser)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Carburant')
            ->assertDontSee('Salaire direction');

        $this->actingAs($regularUser)->post(route('expenses.store'), [
            'expense_category_id' => $salary->id,
            'amount' => 100000,
            'expense_date' => now()->toDateString(),
            'description' => 'Salaire confidentiel',
        ])->assertServerError();

        $this->actingAs($sensitiveUser)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Salaire direction');
    }

    public function test_filters_by_period_category_and_status(): void
    {
        $user = $this->userWithPermissions(['expenses.view']);
        $transport = ExpenseCategory::factory()->create(['name' => 'Transport']);
        $internet = ExpenseCategory::factory()->create(['name' => 'Internet']);

        Expense::factory()->create([
            'expense_category_id' => $transport->id,
            'status' => ExpenseStatus::Validated,
            'expense_date' => '2026-05-10',
            'description' => 'Transport visible',
        ]);
        Expense::factory()->create([
            'expense_category_id' => $internet->id,
            'status' => ExpenseStatus::Draft,
            'expense_date' => '2026-04-10',
            'description' => 'Internet hors filtre',
        ]);

        $this->actingAs($user)->get(route('expenses.index', [
            'category_id' => $transport->id,
            'status' => ExpenseStatus::Validated->value,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]))
            ->assertOk()
            ->assertSee('Transport visible')
            ->assertDontSee('Internet hors filtre');
    }

    private function sequence(): void
    {
        DocumentNumberSequence::query()->create([
            'document_type' => 'expense',
            'prefix' => 'DEP',
            'next_number' => 1,
            'padding' => 5,
            'reset_period' => 'yearly',
            'last_generated_at' => null,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::factory()->create();

        foreach ($permissions as $slug) {
            $permission = Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $slug,
                    'module' => str($slug)->before('.')->toString(),
                    'action' => str($slug)->after('.')->toString(),
                    'is_sensitive' => false,
                    'description' => null,
                ]
            );

            $role->permissions()->attach($permission);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
