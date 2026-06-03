<?php

namespace Tests\Feature\Stock;

use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Models\Client;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockSuspense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_entry_updates_physical_stock_and_is_historized(): void
    {
        $user = $this->userWithPermissions(['stock.view', 'stock.create_entry']);
        $product = Product::factory()->create(['physical_stock' => 5]);

        $this->actingAs($user)->post(route('stock.movements.store'), [
            'product_id' => $product->id,
            'type' => StockMovementType::PurchaseEntry->value,
            'stock_column' => 'physical_stock',
            'quantity' => 7,
            'unit_cost' => 1000,
            'reason' => 'Réception fournisseur.',
        ])->assertRedirect(route('stock.movements'));

        $this->assertSame(12.0, (float) $product->refresh()->physical_stock);

        $movement = StockMovement::query()->firstOrFail();
        $this->assertSame(StockMovementStatus::Validated, $movement->status);
        $this->assertSame(5.0, (float) $movement->physical_before);
        $this->assertSame(12.0, (float) $movement->physical_after);
    }

    public function test_manual_exit_blocks_negative_stock(): void
    {
        $user = $this->userWithPermissions(['stock.create_exit']);
        $product = Product::factory()->create(['physical_stock' => 2]);

        $this->actingAs($user)->post(route('stock.movements.store'), [
            'product_id' => $product->id,
            'type' => StockMovementType::InternalUse->value,
            'stock_column' => 'physical_stock',
            'quantity' => 3,
            'reason' => 'Sortie atelier.',
        ])->assertServerError();

        $this->assertSame(2.0, (float) $product->refresh()->physical_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_adjustment_requires_reason_and_validation_before_affecting_stock(): void
    {
        $creator = $this->userWithPermissions(['stock.adjust', 'stock.view']);
        $validator = $this->userWithPermissions(['stock.validate_movement', 'stock.view']);
        $product = Product::factory()->create(['physical_stock' => 10]);

        $this->actingAs($creator)->from(route('stock.adjustments.create'))->post(route('stock.movements.store'), [
            'product_id' => $product->id,
            'type' => StockMovementType::NegativeAdjustment->value,
            'stock_column' => 'physical_stock',
            'quantity' => 2,
            'reason' => '',
        ])->assertRedirect(route('stock.adjustments.create'))->assertSessionHasErrors('reason');

        $this->actingAs($creator)->post(route('stock.movements.store'), [
            'product_id' => $product->id,
            'type' => StockMovementType::NegativeAdjustment->value,
            'stock_column' => 'physical_stock',
            'quantity' => 2,
            'reason' => 'Inventaire physique inférieur.',
        ])->assertRedirect(route('stock.movements'));

        $movement = StockMovement::query()->firstOrFail();

        $this->assertSame(StockMovementStatus::PendingValidation, $movement->status);
        $this->assertSame(10.0, (float) $product->refresh()->physical_stock);

        $this->actingAs($validator)->post(route('stock.movements.validate', $movement))
            ->assertRedirect(route('stock.movements'));

        $this->assertSame(StockMovementStatus::Validated, $movement->refresh()->status);
        $this->assertSame(8.0, (float) $product->refresh()->physical_stock);
    }

    public function test_stock_pages_reports_and_pdf_are_accessible(): void
    {
        $user = $this->userWithPermissions(['stock.view', 'stock.export']);
        $product = Product::factory()->create([
            'physical_stock' => 2,
            'reserved_stock' => 1,
            'tool_stock' => 4,
            'alert_threshold' => 5,
        ]);
        $client = Client::factory()->create();
        $deliveryNote = DeliveryNote::factory()->delivered()->create(['client_id' => $client->id]);
        $deliveryNoteItem = DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
        ]);
        $invoice = Invoice::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'client_id' => $client->id,
        ]);
        StockSuspense::query()->create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'delivery_note_id' => $deliveryNote->id,
            'delivery_note_item_id' => $deliveryNoteItem->id,
            'invoice_id' => $invoice->id,
            'quantity' => 2,
            'closed_quantity' => 0,
            'status' => 'open',
            'delivered_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('stock.physical'))->assertOk()->assertSee($product->name);
        $this->actingAs($user)->get(route('stock.reserved'))->assertOk()->assertSee($product->name);
        $this->actingAs($user)->get(route('stock.tool'))->assertOk()->assertSee($product->name);
        $this->actingAs($user)->get(route('stock.suspense'))->assertOk()->assertSee($client->name);
        $this->actingAs($user)->get(route('stock.reports.low-stock'))->assertOk()->assertSee($product->name);
        $this->actingAs($user)->get(route('stock.reports.pdf', ['report' => 'low_stock']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
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
