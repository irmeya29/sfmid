<?php

namespace Tests\Feature\Reports;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\StockSuspense;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_show_statistics_with_filters(): void
    {
        $this->seed(PermissionSeeder::class);

        $viewer = $this->userWithPermissions([
            'reports.view_sales',
            'reports.view_finance',
            'reports.view_stock',
            'reports.view_expenses',
        ]);

        [$client, $category] = $this->seedReportData();

        $this->actingAs($viewer)
            ->get(route('reports.index', [
                'client_id' => $client->id,
                'product_category_id' => $category->id,
                'date_from' => now()->subDay()->toDateString(),
                'date_to' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Ventes par periode')
            ->assertSee('Factures impayees')
            ->assertSee('Paiements encaisses')
            ->assertSee('Stock bas')
            ->assertDontSee('Marge par produit');
    }

    public function test_margin_is_visible_only_with_permission_and_exports_work(): void
    {
        $this->seed(PermissionSeeder::class);

        $viewer = $this->userWithPermissions([
            'reports.view_sales',
            'reports.export_pdf',
            'reports.export_excel',
            'products.view_margin',
        ]);

        $this->seedReportData();

        $this->actingAs($viewer)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Marge par produit');

        $this->actingAs($viewer)->get(route('reports.pdf'))->assertOk();

        $this->actingAs($viewer)->get(route('reports.unpaid-invoices.pdf'))->assertOk();

        $this->actingAs($viewer)
            ->get(route('reports.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
    }

    private function seedReportData(): array
    {
        $client = Client::factory()->create(['name' => 'Client Rapport']);
        $category = ProductCategory::factory()->create(['name' => 'Categorie Rapport']);
        $product = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Produit Rapport',
            'purchase_price' => 1000,
            'physical_stock' => 2,
            'alert_threshold' => 5,
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => InvoiceStatus::PartiallyPaid,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'total' => 10000,
            'paid_amount' => 4000,
            'balance_due' => 6000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 5000,
            'line_total' => 10000,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::Validated,
            'amount' => 4000,
            'payment_date' => now()->toDateString(),
        ]);

        $expenseCategory = ExpenseCategory::factory()->create(['name' => 'Transport Rapport']);
        Expense::factory()->create([
            'expense_category_id' => $expenseCategory->id,
            'status' => ExpenseStatus::Validated,
            'expense_date' => now()->toDateString(),
            'amount' => 1500,
        ]);

        StockSuspense::factory()->create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'closed_quantity' => 0,
            'status' => 'open',
        ]);

        return [$client, $category];
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
