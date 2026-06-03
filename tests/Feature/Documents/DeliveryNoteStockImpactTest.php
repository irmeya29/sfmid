<?php

namespace Tests\Feature\Documents;

use App\Actions\Stock\MarkDeliveryNoteAsDeliveredAction;
use App\Enums\DeliveryNoteStatus;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockSuspense;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeliveryNoteStockImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_delivery_note_moves_physical_stock_to_suspense_stock(): void
    {
        $actor = $this->userWithPermissions([
            'delivery_notes.mark_delivered',
        ]);

        $creator = User::factory()->create();

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
                'created_by' => $creator->id,
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
            'line_total' => 75000,
        ]);

        $delivered = app(MarkDeliveryNoteAsDeliveredAction::class)->execute(
            deliveryNote: $deliveryNote,
            user: $actor,
            receiverName: 'Responsable réception',
            receiverPhone: '70000000'
        );

        $product->refresh();

        $this->assertSame(DeliveryNoteStatus::Delivered, $delivered->status);
        $this->assertNotNull($delivered->stock_moved_at);
        $this->assertSame(7.0, (float) $product->physical_stock);
        $this->assertSame(3.0, (float) $product->suspense_stock);

        $this->assertDatabaseCount('stock_suspenses', 1);
        $this->assertDatabaseCount('stock_movements', 1);

        $suspense = StockSuspense::query()->firstOrFail();

        $this->assertSame('open', $suspense->status);
        $this->assertSame(3.0, (float) $suspense->quantity);
        $this->assertSame(0.0, (float) $suspense->closed_quantity);
        $this->assertSame($deliveryNote->id, $suspense->delivery_note_id);
        $this->assertSame($product->id, $suspense->product_id);

        $movement = StockMovement::query()->firstOrFail();

        $this->assertSame(10.0, (float) $movement->physical_before);
        $this->assertSame(7.0, (float) $movement->physical_after);
        $this->assertSame(0.0, (float) $movement->suspense_before);
        $this->assertSame(3.0, (float) $movement->suspense_after);
    }

    public function test_delivery_note_stock_cannot_be_moved_twice(): void
    {
        $actor = $this->userWithPermissions([
            'delivery_notes.mark_delivered',
        ]);

        $product = Product::factory()->create([
            'physical_stock' => 10,
            'suspense_stock' => 0,
        ]);

        $deliveryNote = DeliveryNote::factory()
            ->validated()
            ->create();

        DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 3,
            'delivered_quantity' => 3,
        ]);

        app(MarkDeliveryNoteAsDeliveredAction::class)->execute($deliveryNote, $actor);

        $this->expectException(AuthorizationException::class);

        app(MarkDeliveryNoteAsDeliveredAction::class)->execute($deliveryNote->refresh(), $actor);

        $product->refresh();

        $this->assertSame(7.0, (float) $product->physical_stock);
        $this->assertSame(3.0, (float) $product->suspense_stock);
    }

    public function test_delivery_note_delivery_fails_when_physical_stock_is_insufficient(): void
    {
        $actor = $this->userWithPermissions([
            'delivery_notes.mark_delivered',
        ]);

        $product = Product::factory()->create([
            'physical_stock' => 2,
            'suspense_stock' => 0,
        ]);

        $deliveryNote = DeliveryNote::factory()
            ->validated()
            ->create();

        DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 3,
            'delivered_quantity' => 3,
        ]);

        $this->expectException(RuntimeException::class);

        app(MarkDeliveryNoteAsDeliveredAction::class)->execute($deliveryNote, $actor);

        $product->refresh();

        $this->assertSame(2.0, (float) $product->physical_stock);
        $this->assertSame(0.0, (float) $product->suspense_stock);
        $this->assertDatabaseCount('stock_suspenses', 0);
        $this->assertDatabaseCount('stock_movements', 0);
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
