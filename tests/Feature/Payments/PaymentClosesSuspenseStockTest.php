<?php

namespace Tests\Feature\Payments;

use App\Actions\Payments\SubmitPaymentAction;
use App\Actions\Payments\ValidatePaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockSuspense;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentClosesSuspenseStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_validated_payment_marks_invoice_paid_without_closing_suspense_stock(): void
    {
        $actor = $this->userWithPermissions([
            'payments.submit',
            'payments.validate',
        ]);

        $creator = User::factory()->create();

        $product = Product::factory()->create([
            'physical_stock' => 7,
            'suspense_stock' => 3,
            'purchase_price' => 1000,
            'sale_price' => 25000,
        ]);

        $deliveryNote = DeliveryNote::factory()
            ->delivered()
            ->create([
                'created_by' => $creator->id,
            ]);

        $deliveryNoteItem = DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 3,
            'delivered_quantity' => 3,
            'unit_price' => 25000,
            'line_total' => 75000,
        ]);

        $invoice = Invoice::factory()
            ->validated()
            ->create([
                'delivery_note_id' => $deliveryNote->id,
                'client_id' => $deliveryNote->client_id,
                'total' => 75000,
                'paid_amount' => 0,
                'balance_due' => 75000,
            ]);

        StockSuspense::query()->create([
            'client_id' => $deliveryNote->client_id,
            'product_id' => $product->id,
            'delivery_note_id' => $deliveryNote->id,
            'delivery_note_item_id' => $deliveryNoteItem->id,
            'invoice_id' => $invoice->id,
            'quantity' => 3,
            'closed_quantity' => 0,
            'status' => 'open',
            'delivered_at' => now(),
            'closed_at' => null,
            'closing_reason' => null,
            'created_by' => $creator->id,
            'closed_by' => null,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::Draft,
            'amount' => 75000,
            'created_by' => $creator->id,
        ]);

        $submitted = app(SubmitPaymentAction::class)->execute($payment, $actor);
        $validatedPayment = app(ValidatePaymentAction::class)->execute($submitted, $actor);

        $invoice->refresh();
        $product->refresh();

        $this->assertSame(PaymentStatus::Validated, $validatedPayment->status);
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(75000.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->balance_due);

        $this->assertSame(7.0, (float) $product->physical_stock);
        $this->assertSame(3.0, (float) $product->suspense_stock);

        $suspense = StockSuspense::query()->firstOrFail();

        $this->assertSame('open', $suspense->status);
        $this->assertSame(0.0, (float) $suspense->closed_quantity);
        $this->assertNull($suspense->closed_at);
        $this->assertNull($suspense->closed_by);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_partial_payment_marks_invoice_partially_paid_without_closing_suspense_stock(): void
    {
        $actor = $this->userWithPermissions([
            'payments.submit',
            'payments.validate',
        ]);

        $creator = User::factory()->create();

        $product = Product::factory()->create([
            'physical_stock' => 7,
            'suspense_stock' => 3,
        ]);

        $deliveryNote = DeliveryNote::factory()
            ->delivered()
            ->create();

        $deliveryNoteItem = DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
        ]);

        $invoice = Invoice::factory()
            ->validated()
            ->create([
                'delivery_note_id' => $deliveryNote->id,
                'client_id' => $deliveryNote->client_id,
                'total' => 75000,
                'paid_amount' => 0,
                'balance_due' => 75000,
            ]);

        StockSuspense::query()->create([
            'client_id' => $deliveryNote->client_id,
            'product_id' => $product->id,
            'delivery_note_id' => $deliveryNote->id,
            'delivery_note_item_id' => $deliveryNoteItem->id,
            'invoice_id' => $invoice->id,
            'quantity' => 3,
            'closed_quantity' => 0,
            'status' => 'open',
            'delivered_at' => now(),
            'created_by' => $creator->id,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::Draft,
            'amount' => 25000,
            'created_by' => $creator->id,
        ]);

        $submitted = app(SubmitPaymentAction::class)->execute($payment, $actor);

        app(ValidatePaymentAction::class)->execute($submitted, $actor);

        $invoice->refresh();
        $product->refresh();

        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame(25000.0, (float) $invoice->paid_amount);
        $this->assertSame(50000.0, (float) $invoice->balance_due);

        $this->assertSame(3.0, (float) $product->suspense_stock);

        $suspense = StockSuspense::query()->firstOrFail();

        $this->assertSame('open', $suspense->status);
        $this->assertSame(0.0, (float) $suspense->closed_quantity);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_payment_amount_cannot_exceed_invoice_balance(): void
    {
        $actor = $this->userWithPermissions([
            'payments.submit',
        ]);

        $invoice = Invoice::factory()
            ->validated()
            ->create([
                'total' => 50000,
                'paid_amount' => 0,
                'balance_due' => 50000,
            ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::Draft,
            'amount' => 60000,
        ]);

        $this->expectException(RuntimeException::class);

        app(SubmitPaymentAction::class)->execute($payment, $actor);
    }

    public function test_creator_cannot_validate_own_payment_without_sensitive_permission(): void
    {
        $creator = $this->userWithPermissions([
            'payments.validate',
        ]);

        $invoice = Invoice::factory()
            ->validated()
            ->create([
                'total' => 50000,
                'paid_amount' => 0,
                'balance_due' => 50000,
            ]);

        $payment = Payment::factory()
            ->pendingValidation()
            ->create([
                'invoice_id' => $invoice->id,
                'amount' => 50000,
                'created_by' => $creator->id,
            ]);

        $this->expectException(AuthorizationException::class);

        app(ValidatePaymentAction::class)->execute($payment, $creator);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::factory()->create();

        foreach ($permissions as $slug) {
            $permission = Permission::query()->create([
                'name' => $slug,
                'slug' => $slug,
                'module' => str($slug)->before('.')->toString(),
                'action' => str($slug)->after('.')->toString(),
                'is_sensitive' => false,
                'description' => null,
            ]);

            $role->permissions()->attach($permission);
        }

        $user = User::factory()->create();

        $user->roles()->attach($role);

        return $user;
    }
}
