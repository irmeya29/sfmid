<?php

namespace Tests\Feature\Documents;

use App\Enums\DeliveryNoteStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\DocumentNumberSequence;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockSuspense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePaymentHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created_from_delivered_delivery_note_and_validated(): void
    {
        $this->sequence('invoice', 'FAC');

        $creator = $this->userWithPermissions([
            'invoices.view',
            'invoices.create',
            'invoices.submit',
            'delivery_notes.convert_to_invoice',
        ]);
        $validator = $this->userWithPermissions([
            'invoices.validate',
            'invoices.export_pdf',
        ]);

        [$deliveryNote, $product] = $this->deliveredDeliveryNote();

        $this->actingAs($creator)->get(route('invoices.create', ['delivery_note_id' => $deliveryNote->id]))
            ->assertOk()
            ->assertSee($deliveryNote->number);

        $this->actingAs($creator)->post(route('invoices.store'), [
            'source_type' => 'delivery_note',
            'delivery_note_id' => $deliveryNote->id,
        ])->assertRedirect();

        $invoice = Invoice::query()->firstOrFail();

        $this->assertSame('FAC-'.now()->format('Y').'-00001', $invoice->number);
        $this->assertSame($deliveryNote->id, $invoice->delivery_note_id);
        $this->assertSame(7.0, (float) $product->refresh()->physical_stock);
        $this->assertSame(3.0, (float) $product->suspense_stock);

        $this->actingAs($creator)->post(route('invoices.submit', $invoice))
            ->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame(InvoiceStatus::PendingValidation, $invoice->refresh()->status);

        $this->actingAs($validator)->post(route('invoices.validate', $invoice))
            ->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->refresh()->status);

        $this->actingAs($validator)->get(route('invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_creation_is_forbidden_for_non_delivered_or_already_invoiced_delivery_note(): void
    {
        $this->sequence('invoice', 'FAC');

        $user = $this->userWithPermissions(['invoices.create', 'delivery_notes.convert_to_invoice']);

        $draftDeliveryNote = DeliveryNote::factory()->create([
            'status' => DeliveryNoteStatus::Validated,
            'stock_moved_at' => null,
        ]);
        DeliveryNoteItem::factory()->create(['delivery_note_id' => $draftDeliveryNote->id]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'source_type' => 'delivery_note',
            'delivery_note_id' => $draftDeliveryNote->id,
        ])->assertForbidden();

        [$deliveredDeliveryNote] = $this->deliveredDeliveryNote();

        $this->actingAs($user)->post(route('invoices.store'), [
            'source_type' => 'delivery_note',
            'delivery_note_id' => $deliveredDeliveryNote->id,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('invoices.store'), [
            'source_type' => 'delivery_note',
            'delivery_note_id' => $deliveredDeliveryNote->id,
        ])->assertForbidden();
    }

    public function test_invoice_can_be_created_independently_without_stock_movement(): void
    {
        $this->sequence('invoice', 'FAC');

        $user = $this->userWithPermissions(['invoices.view', 'invoices.create']);
        $product = Product::factory()->create([
            'physical_stock' => 10,
            'suspense_stock' => 0,
            'sale_price' => 25000,
        ]);

        $this->actingAs($user)->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('Facture directe');

        $this->actingAs($user)->post(route('invoices.store'), [
            'source_type' => 'direct',
            'client_id' => \App\Models\Client::factory()->create()->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'payment_terms' => 'Paiement comptant',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 25000,
                    'discount_amount' => 5000,
                ],
            ],
        ])->assertRedirect();

        $invoice = Invoice::query()->with('items')->firstOrFail();

        $this->assertNull($invoice->delivery_note_id);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame(50000.0, (float) $invoice->subtotal);
        $this->assertSame(5000.0, (float) $invoice->discount_total);
        $this->assertSame(45000.0, (float) $invoice->total);
        $this->assertSame(45000.0, (float) $invoice->balance_due);
        $this->assertSame(10.0, (float) $product->refresh()->physical_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_payment_partial_total_validation_receipt_and_cash_journal(): void
    {
        $this->sequence('payment', 'REC');
        PaymentMode::query()->create(['name' => 'Espèces', 'code' => 'cash', 'is_active' => true]);

        $creator = $this->userWithPermissions([
            'payments.view',
            'payments.create',
            'payments.submit',
            'payments.export_receipt_pdf',
        ]);
        $validator = $this->userWithPermissions([
            'payments.validate',
            'payments.view',
            'payments.export_receipt_pdf',
        ]);

        [$deliveryNote, $product, $deliveryNoteItem] = $this->deliveredDeliveryNote();
        $invoice = Invoice::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'client_id' => $deliveryNote->client_id,
            'status' => InvoiceStatus::Unpaid,
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

        $this->actingAs($creator)->get(route('payments.create', ['invoice_id' => $invoice->id]))
            ->assertOk()
            ->assertSee($invoice->number);

        $this->actingAs($creator)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 25000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ])->assertRedirect();

        $firstPayment = Payment::query()->firstOrFail();

        $this->actingAs($creator)->post(route('payments.submit', $firstPayment))->assertRedirect(route('payments.show', $firstPayment));
        $this->actingAs($validator)->post(route('payments.validate', $firstPayment))->assertRedirect(route('payments.show', $firstPayment));
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->refresh()->status);
        $this->assertSame(50000.0, (float) $invoice->balance_due);
        $this->assertSame(3.0, (float) $product->refresh()->suspense_stock);

        $this->actingAs($creator)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ])->assertRedirect();

        $secondPayment = Payment::query()->latest('id')->firstOrFail();

        $this->actingAs($creator)->post(route('payments.submit', $secondPayment))->assertRedirect(route('payments.show', $secondPayment));
        $this->actingAs($validator)->post(route('payments.validate', $secondPayment))->assertRedirect(route('payments.show', $secondPayment));
        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
        $this->assertSame(0.0, (float) $invoice->balance_due);
        $this->assertSame(0.0, (float) $product->refresh()->suspense_stock);

        $this->actingAs($validator)->get(route('payments.receipt', $secondPayment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($validator)->get(route('payments.cash-journal'))->assertOk()->assertSee($secondPayment->number);
    }

    public function test_payment_cannot_exceed_invoice_balance_and_can_be_rejected(): void
    {
        $this->sequence('payment', 'REC');
        PaymentMode::query()->create(['name' => 'Espèces', 'code' => 'cash', 'is_active' => true]);

        $creator = $this->userWithPermissions(['payments.create']);
        $rejector = $this->userWithPermissions(['payments.reject']);

        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Unpaid,
            'total' => 10000,
            'balance_due' => 10000,
        ]);

        $this->actingAs($creator)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 20000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ])->assertServerError();

        $payment = \App\Models\Payment::factory()->pendingValidation()->create([
            'invoice_id' => $invoice->id,
            'created_by' => $creator->id,
            'amount' => 5000,
        ]);

        $this->actingAs($rejector)->post(route('payments.reject', $payment), [
            'reason' => 'Référence bancaire illisible.',
        ])->assertRedirect(route('payments.show', $payment));

        $this->assertSame(PaymentStatus::Rejected, $payment->refresh()->status);
    }

    /**
     * @return array{0: DeliveryNote, 1: Product, 2: DeliveryNoteItem}
     */
    private function deliveredDeliveryNote(): array
    {
        $product = Product::factory()->create([
            'physical_stock' => 7,
            'suspense_stock' => 3,
        ]);
        $deliveryNote = DeliveryNote::factory()->delivered()->create([
            'subtotal' => 75000,
            'total' => 75000,
        ]);
        $item = DeliveryNoteItem::factory()->create([
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

        return [$deliveryNote, $product, $item];
    }

    private function sequence(string $documentType, string $prefix): void
    {
        DocumentNumberSequence::query()->create([
            'document_type' => $documentType,
            'prefix' => $prefix,
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
