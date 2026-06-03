<?php

namespace Tests\Feature\Purchases;

use App\Models\DocumentNumberSequence;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPurchaseOrder;
use App\Models\User;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\DocumentNumberSequenceSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPurchaseHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_crud_order_invoice_payment_and_pdf_workflow(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(DocumentNumberSequenceSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $user = $this->userWithPermissions([
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.manage_products',
            'purchases.view',
            'purchases.create_request',
            'purchases.create_order',
            'purchases.receive_invoice',
            'purchases.pay_supplier',
            'purchases.export_pdf',
        ]);

        $product = Product::factory()->create(['name' => 'Cable fournisseur', 'purchase_price' => 1000]);

        $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'Fournisseur Test',
            'phone' => '70000000',
            'email' => 'four@test.local',
            'is_active' => 1,
            'product_ids' => [$product->id],
        ])->assertRedirect();

        $supplier = Supplier::query()->where('name', 'Fournisseur Test')->firstOrFail();
        $this->assertStringStartsWith('FOU-', $supplier->code);
        $this->assertTrue($supplier->products()->whereKey($product->id)->exists());

        $this->actingAs($user)->post(route('purchases.requests.store'), [
            'supplier_id' => $supplier->id,
            'request_date' => now()->toDateString(),
            'notes' => 'Besoin stock.',
        ])->assertRedirect(route('purchases.index'));

        $this->actingAs($user)->post(route('purchases.orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'terms' => 'Paiement après livraison.',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1500],
            ],
        ])->assertRedirect();

        $order = SupplierPurchaseOrder::query()->where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame('ordered', $order->status);
        $this->assertEquals(4500, (float) $order->subtotal);

        $this->actingAs($user)->get(route('purchases.orders.pdf', $order))->assertOk();

        $this->actingAs($user)->post(route('purchases.invoices.store'), [
            'supplier_id' => $supplier->id,
            'supplier_purchase_order_id' => $order->id,
            'supplier_invoice_number' => 'FAC-FOU-01',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total' => 4500,
        ])->assertRedirect(route('suppliers.show', $supplier));

        $invoice = SupplierInvoice::query()->where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertEquals(4500, (float) $invoice->balance_due);

        $this->actingAs($user)->post(route('purchases.payments.store'), [
            'supplier_invoice_id' => $invoice->id,
            'amount' => 2000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ])->assertRedirect(route('suppliers.show', $supplier));

        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertEquals(2500, (float) $invoice->balance_due);
    }

    public function test_supplier_payment_cannot_exceed_supplier_debt(): void
    {
        $this->seed(PermissionSeeder::class);
        DocumentNumberSequence::query()->create(['document_type' => 'supplier_payment', 'prefix' => 'RFO', 'next_number' => 1, 'padding' => 5]);

        $user = $this->userWithPermissions(['purchases.pay_supplier']);
        $supplier = Supplier::query()->create(['code' => 'FOU-00001', 'name' => 'Dette Test']);
        $invoice = SupplierInvoice::query()->create([
            'number' => 'FAF-00001',
            'supplier_id' => $supplier->id,
            'status' => 'unpaid',
            'invoice_date' => now(),
            'total' => 1000,
            'paid_amount' => 0,
            'balance_due' => 1000,
        ]);

        $this->actingAs($user)->post(route('purchases.payments.store'), [
            'supplier_invoice_id' => $invoice->id,
            'amount' => 1500,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ])->assertRedirect();

        $this->assertEquals(1000, (float) $invoice->fresh()->balance_due);
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
