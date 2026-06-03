<?php

namespace Tests\Feature\Documents;

use App\Actions\Documents\ConvertDeliveryNoteToInvoiceAction;
use App\Actions\Stock\MarkDeliveryNoteAsDeliveredAction;
use App\Enums\DeliveryNoteStatus;
use App\Enums\InvoiceStatus;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\DocumentNumberSequence;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockSuspense;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNoDoubleStockDeductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_created_from_delivered_delivery_note_does_not_deduct_stock_again(): void
    {
        $this->createInvoiceSequence();

        $actor = $this->userWithPermissions([
            'delivery_notes.mark_delivered',
            'delivery_notes.convert_to_invoice',
        ]);

        $product = Product::factory()->create([
            'physical_stock' => 10,
            'reserved_stock' => 0,
            'suspense_stock' => 0,
            'purchase_price' => 1000,
            'sale_price' => 25000,
        ]);

        $deliveryNote = DeliveryNote::factory()
            ->validated()
            ->create([
                'subtotal' => 75000,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 75000,
            ]);

        DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 3,
            'delivered_quantity' => 3,
            'unit_price' => 25000,
            'discount_amount' => 0,
            'line_total' => 75000,
        ]);

        $delivered = app(MarkDeliveryNoteAsDeliveredAction::class)->execute($deliveryNote, $actor);

        $product->refresh();

        $this->assertSame(7.0, (float) $product->physical_stock);
        $this->assertSame(3.0, (float) $product->suspense_stock);

        $invoice = app(ConvertDeliveryNoteToInvoiceAction::class)->execute($delivered, $actor);

        $product->refresh();

        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame(75000.0, (float) $invoice->total);
        $this->assertSame(75000.0, (float) $invoice->balance_due);
        $this->assertCount(1, $invoice->items);

        $this->assertSame(7.0, (float) $product->physical_stock);
        $this->assertSame(3.0, (float) $product->suspense_stock);

        $this->assertSame(DeliveryNoteStatus::Invoiced, $delivered->refresh()->status);

        $suspense = StockSuspense::query()->firstOrFail();

        $this->assertSame($invoice->id, $suspense->invoice_id);
        $this->assertSame('open', $suspense->status);
        $this->assertSame(3.0, (float) $suspense->quantity);
        $this->assertSame(0.0, (float) $suspense->closed_quantity);
    }

    public function test_delivery_note_cannot_be_invoiced_twice(): void
    {
        $this->createInvoiceSequence();

        $actor = $this->userWithPermissions([
            'delivery_notes.mark_delivered',
            'delivery_notes.convert_to_invoice',
        ]);

        $product = Product::factory()->create([
            'physical_stock' => 10,
            'suspense_stock' => 0,
            'sale_price' => 25000,
        ]);

        $deliveryNote = DeliveryNote::factory()
            ->validated()
            ->create([
                'subtotal' => 50000,
                'total' => 50000,
            ]);

        DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 2,
            'delivered_quantity' => 2,
            'unit_price' => 25000,
            'line_total' => 50000,
        ]);

        $delivered = app(MarkDeliveryNoteAsDeliveredAction::class)->execute($deliveryNote, $actor);

        app(ConvertDeliveryNoteToInvoiceAction::class)->execute($delivered, $actor);

        $this->expectException(AuthorizationException::class);

        app(ConvertDeliveryNoteToInvoiceAction::class)->execute($delivered->refresh(), $actor);
    }

    public function test_non_delivered_delivery_note_cannot_be_converted_to_invoice(): void
    {
        $this->createInvoiceSequence();

        $actor = $this->userWithPermissions([
            'delivery_notes.convert_to_invoice',
        ]);

        $deliveryNote = DeliveryNote::factory()
            ->validated()
            ->create();

        $this->expectException(AuthorizationException::class);

        app(ConvertDeliveryNoteToInvoiceAction::class)->execute($deliveryNote, $actor);
    }

    private function createInvoiceSequence(): void
    {
        DocumentNumberSequence::query()->create([
            'document_type' => 'invoice',
            'prefix' => 'FAC',
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
