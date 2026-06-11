<?php

namespace Tests\Feature\Documents;

use App\Enums\DeliveryNoteStatus;
use App\Models\Client;
use App\Models\ClientDeliverySite;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\DocumentNumberSequence;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNoteHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_manual_delivery_note_crud(): void
    {
        $this->createSequence('delivery_note', 'BL');

        $user = $this->userWithPermissions([
            'delivery_notes.view',
            'delivery_notes.create',
            'delivery_notes.update',
            'delivery_notes.delete_draft',
        ]);

        $client = Client::factory()->create();
        $site = ClientDeliverySite::factory()->default()->create([
            'client_id' => $client->id,
            'name' => 'Site Ouaga',
        ]);
        $product = Product::factory()->create([
            'sale_price' => 12000,
            'physical_stock' => 8,
        ]);

        $this->actingAs($user)->get(route('delivery-notes.index'))->assertOk();
        $this->actingAs($user)->get(route('delivery-notes.create'))
            ->assertOk()
            ->assertSee('Site Ouaga');

        $storeResponse = $this->actingAs($user)->post(route('delivery-notes.store'), [
            'client_id' => $client->id,
            'client_delivery_site_id' => $site->id,
            'planned_delivery_date' => now()->addDay()->toDateString(),
            'subject' => 'Livraison de flexibles hydrauliques',
            'notes' => 'Livraison matin.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'delivered_quantity' => 2,
                    'unit_price' => 12000,
                    'discount_amount' => 1000,
                ],
            ],
        ]);

        $deliveryNote = DeliveryNote::query()->firstOrFail();

        $storeResponse->assertRedirect(route('delivery-notes.show', $deliveryNote));
        $this->assertSame('BL-'.now()->format('Y').'-00001', $deliveryNote->number);
        $this->assertSame('Livraison de flexibles hydrauliques', $deliveryNote->subject);
        $this->assertSame('23000.00', $deliveryNote->total);

        $this->actingAs($user)->get(route('delivery-notes.show', $deliveryNote))
            ->assertOk()
            ->assertSee('Site Ouaga');

        $this->actingAs($user)->put(route('delivery-notes.update', $deliveryNote), [
            'client_id' => $client->id,
            'client_delivery_site_id' => $site->id,
            'planned_delivery_date' => now()->addDays(2)->toDateString(),
            'subject' => 'Livraison de raccords hydrauliques',
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'delivered_quantity' => 3,
                    'unit_price' => 12000,
                    'discount_amount' => 0,
                ],
            ],
        ])->assertRedirect(route('delivery-notes.show', $deliveryNote));

        $this->assertSame('Livraison de raccords hydrauliques', $deliveryNote->refresh()->subject);
        $this->assertSame('36000.00', $deliveryNote->refresh()->total);

        $this->actingAs($user)->delete(route('delivery-notes.destroy', $deliveryNote))
            ->assertRedirect(route('delivery-notes.index'));

        $this->assertSoftDeleted($deliveryNote);
    }

    public function test_delivery_note_rejects_duplicate_products_and_site_from_another_client(): void
    {
        $user = $this->userWithPermissions(['delivery_notes.create']);
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $otherSite = ClientDeliverySite::factory()->create(['client_id' => $otherClient->id]);
        $product = Product::factory()->create();

        $this->actingAs($user)->from(route('delivery-notes.create'))->post(route('delivery-notes.store'), [
            'client_id' => $client->id,
            'client_delivery_site_id' => $otherSite->id,
            'planned_delivery_date' => now()->toDateString(),
            'subject' => 'Livraison de pieces',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'delivered_quantity' => 1, 'unit_price' => 1000, 'discount_amount' => 0],
                ['product_id' => $product->id, 'quantity' => 1, 'delivered_quantity' => 1, 'unit_price' => 1000, 'discount_amount' => 0],
            ],
        ])
            ->assertRedirect(route('delivery-notes.create'))
            ->assertSessionHasErrors(['client_delivery_site_id', 'items.1.product_id']);
    }

    public function test_delivery_note_workflow_from_http_moves_stock_once(): void
    {
        $creator = $this->userWithPermissions([
            'delivery_notes.view',
            'delivery_notes.submit',
            'delivery_notes.mark_prepared',
            'delivery_notes.mark_delivered',
        ]);
        $validator = $this->userWithPermissions([
            'delivery_notes.validate',
        ]);

        $product = Product::factory()->create([
            'physical_stock' => 10,
            'suspense_stock' => 0,
        ]);
        $deliveryNote = DeliveryNote::factory()->create([
            'created_by' => $creator->id,
        ]);
        DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 4,
            'delivered_quantity' => 4,
        ]);

        $this->actingAs($creator)->post(route('delivery-notes.submit', $deliveryNote))
            ->assertRedirect(route('delivery-notes.show', $deliveryNote));
        $this->assertSame(DeliveryNoteStatus::PendingValidation, $deliveryNote->refresh()->status);

        $this->actingAs($validator)->post(route('delivery-notes.validate', $deliveryNote))
            ->assertRedirect(route('delivery-notes.show', $deliveryNote));
        $this->assertSame(DeliveryNoteStatus::Validated, $deliveryNote->refresh()->status);

        $this->actingAs($creator)->post(route('delivery-notes.mark-prepared', $deliveryNote))
            ->assertRedirect(route('delivery-notes.show', $deliveryNote));
        $this->assertSame(DeliveryNoteStatus::Prepared, $deliveryNote->refresh()->status);

        $this->actingAs($creator)->post(route('delivery-notes.mark-delivered', $deliveryNote), [
            'receiver_name' => 'Responsable réception',
            'receiver_phone' => '70000000',
            'delivered_at' => now()->format('Y-m-d H:i:s'),
            'delivery_address' => 'Zone industrielle',
        ])->assertRedirect(route('delivery-notes.show', $deliveryNote));

        $this->assertSame(DeliveryNoteStatus::Delivered, $deliveryNote->refresh()->status);
        $this->assertNotNull($deliveryNote->stock_moved_at);
        $this->assertSame(6.0, (float) $product->refresh()->physical_stock);
        $this->assertSame(4.0, (float) $product->suspense_stock);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('stock_suspenses', 1);

        $this->actingAs($creator)->post(route('delivery-notes.mark-delivered', $deliveryNote), [
            'receiver_name' => 'Autre',
            'delivered_at' => now()->format('Y-m-d H:i:s'),
        ])->assertForbidden();

        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_delivery_note_can_be_rejected_with_reason_and_exported_to_pdf(): void
    {
        $creator = User::factory()->create();
        $user = $this->userWithPermissions([
            'delivery_notes.reject',
            'delivery_notes.export_pdf',
        ]);

        $deliveryNote = DeliveryNote::factory()->create([
            'created_by' => $creator->id,
            'status' => DeliveryNoteStatus::PendingValidation,
            'submitted_at' => now(),
        ]);
        DeliveryNoteItem::factory()->create(['delivery_note_id' => $deliveryNote->id]);

        $this->actingAs($user)->post(route('delivery-notes.reject', $deliveryNote), [
            'reason' => 'Adresse de livraison imprécise.',
        ])->assertRedirect(route('delivery-notes.show', $deliveryNote));

        $this->assertSame(DeliveryNoteStatus::Rejected, $deliveryNote->refresh()->status);
        $this->assertSame('Adresse de livraison imprécise.', $deliveryNote->rejection_reason);

        $this->actingAs($user)->get(route('delivery-notes.pdf', $deliveryNote))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function createSequence(string $documentType, string $prefix): void
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
