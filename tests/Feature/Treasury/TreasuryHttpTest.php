<?php

namespace Tests\Feature\Treasury;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\DocumentNumberSequence;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasuryHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_treasury_receipts_are_automatic_from_validated_client_payments(): void
    {
        $this->seed(PermissionSeeder::class);
        $user = $this->userWithPermissions(['treasury.view']);

        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::PartiallyPaid, 'total' => 10000]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::Validated,
            'amount' => 4000,
            'payment_date' => now()->toDateString(),
        ]);
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::Draft,
            'amount' => 2500,
            'payment_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('treasury.index'))
            ->assertOk()
            ->assertSee('Paiement facture '.$invoice->number)
            ->assertSee('4 000')
            ->assertDontSee('2 500');

        $this->assertFalse(collect(app('router')->getRoutes())->contains(fn ($route) => $route->getName() === 'treasury.receipts.store'));
    }

    public function test_current_expense_can_be_entered_directly_in_treasury(): void
    {
        $this->seed(PermissionSeeder::class);
        DocumentNumberSequence::query()->create(['document_type' => 'expense', 'prefix' => 'DEP', 'next_number' => 1, 'padding' => 5]);
        PaymentMode::query()->create(['name' => 'Espèces', 'code' => 'cash', 'is_active' => true]);

        $user = $this->userWithPermissions(['treasury.view', 'treasury.create_expense']);
        $category = ExpenseCategory::factory()->create(['name' => 'Carburant', 'is_active' => true]);

        $this->actingAs($user)->post(route('treasury.expenses.store'), [
            'expense_category_id' => $category->id,
            'amount' => 15000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'beneficiary' => 'Station',
            'description' => 'Carburant mission.',
        ])->assertRedirect(route('treasury.index'));

        $expense = Expense::query()->where('beneficiary', 'Station')->firstOrFail();
        $this->assertSame(ExpenseStatus::Validated, $expense->status);
        $this->assertEquals(15000, (float) $expense->amount);

        $this->actingAs($user)->get(route('treasury.index'))->assertSee('Carburant mission.')->assertSee('15 000');
    }

    public function test_expense_categories_can_be_managed_and_deleted_only_when_unused(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = $this->userWithPermissions([
            'expense_categories.view',
            'expense_categories.create',
            'expense_categories.update',
            'expense_categories.disable',
            'expense_categories.delete',
        ]);

        $this->actingAs($user)->post(route('expense-categories.store'), [
            'name' => 'Frais de mission',
            'description' => 'Deplacements et missions terrain.',
            'is_active' => '1',
        ])->assertRedirect(route('expense-categories.index'));

        $category = ExpenseCategory::query()->where('name', 'Frais de mission')->firstOrFail();
        $this->assertSame('frais-de-mission', $category->slug);
        $this->assertTrue($category->is_active);

        $this->actingAs($user)->get(route('expense-categories.index'))
            ->assertOk()
            ->assertSee('Frais de mission');

        $this->actingAs($user)->put(route('expense-categories.update', $category), [
            'name' => 'Mission',
            'slug' => 'mission',
            'description' => 'Frais de mission terrain.',
            'is_sensitive' => '0',
            'is_active' => '0',
        ])->assertRedirect(route('expense-categories.index'));

        $category->refresh();
        $this->assertSame('Mission', $category->name);
        $this->assertFalse($category->is_active);

        $this->actingAs($user)->delete(route('expense-categories.destroy', $category))
            ->assertRedirect(route('expense-categories.index'));
        $this->assertSoftDeleted('expense_categories', ['id' => $category->id]);

        $usedCategory = ExpenseCategory::factory()->create(['name' => 'Loyer siege']);
        $expense = Expense::factory()->create([
            'expense_category_id' => $usedCategory->id,
            'status' => ExpenseStatus::Validated,
            'description' => 'Loyer mensuel siege',
        ]);

        $this->actingAs($user)->get(route('expense-categories.show', $usedCategory))
            ->assertOk()
            ->assertSee('Loyer siege')
            ->assertSee($expense->number);

        $this->actingAs($user)->delete(route('expense-categories.destroy', $usedCategory))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('expense_categories', ['id' => $usedCategory->id, 'deleted_at' => null]);
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
