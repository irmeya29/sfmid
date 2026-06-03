<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\ClientDeliverySite;
use App\Models\DeliveryNote;
use App\Models\DocumentNumberSequence;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProformaHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_proforma_crud_from_http_routes(): void
    {
        $this->createSequence('proforma', 'PRO');

        $user = $this->userWithPermissions([
            'proformas.view',
            'proformas.create',
            'proformas.update',
            'proformas.delete_draft',
        ]);

        $client = Client::factory()->create([
            'commercial_terms' => 'Paiement 30 jours.',
        ]);
        $site = ClientDeliverySite::factory()->default()->create([
            'client_id' => $client->id,
            'name' => 'Mine Nord',
        ]);
        $product = Product::factory()->create([
            'sale_price' => 15000,
            'physical_stock' => 4,
        ]);

        $this->actingAs($user)->get(route('proformas.index'))->assertOk();
        $this->actingAs($user)->get(route('proformas.create'))
            ->assertOk()
            ->assertSee('Mine Nord');

        $storeResponse = $this->actingAs($user)->post(route('proformas.store'), [
            'client_id' => $client->id,
            'client_delivery_site_id' => $site->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'terms' => 'Paiement comptant.',
            'notes' => 'Livraison urgente.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 15000,
                    'discount_rate' => 10,
                ],
            ],
        ]);

        $proforma = Proforma::query()->firstOrFail();

        $storeResponse->assertRedirect(route('proformas.show', $proforma));
        $this->assertSame($site->id, $proforma->client_delivery_site_id);
        $this->assertSame('PRO-'.now()->format('Y').'-00001', $proforma->number);
        $this->assertDatabaseHas('proforma_items', [
            'proforma_id' => $proforma->id,
            'product_id' => $product->id,
            'line_total' => 27000,
        ]);

        $this->actingAs($user)->get(route('proformas.show', $proforma))
            ->assertOk()
            ->assertSee('Mine Nord');

        $this->actingAs($user)->get(route('proformas.edit', $proforma))->assertOk();

        $this->actingAs($user)->put(route('proformas.update', $proforma), [
            'client_id' => $client->id,
            'client_delivery_site_id' => $site->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'terms' => 'Paiement 15 jours.',
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 15000,
                    'discount_rate' => 0,
                ],
            ],
        ])->assertRedirect(route('proformas.show', $proforma));

        $this->assertSame('45000.00', $proforma->refresh()->total);

        $this->actingAs($user)->delete(route('proformas.destroy', $proforma))
            ->assertRedirect(route('proformas.index'));

        $this->assertSoftDeleted($proforma);
    }

    public function test_store_rejects_duplicate_products_and_site_from_another_client(): void
    {
        $user = $this->userWithPermissions([
            'proformas.create',
        ]);

        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $otherSite = ClientDeliverySite::factory()->create([
            'client_id' => $otherClient->id,
        ]);
        $product = Product::factory()->create();

        $this->actingAs($user)->from(route('proformas.create'))->post(route('proformas.store'), [
            'client_id' => $client->id,
            'client_delivery_site_id' => $otherSite->id,
            'issue_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'discount_rate' => 0,
                ],
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'discount_rate' => 0,
                ],
            ],
        ])
            ->assertRedirect(route('proformas.create'))
            ->assertSessionHasErrors([
                'client_delivery_site_id',
                'items.1.product_id',
            ]);
    }

    public function test_user_can_export_non_draft_proforma_pdf(): void
    {
        $user = $this->userWithPermissions([
            'proformas.export_pdf',
        ]);

        $proforma = Proforma::factory()->pendingValidation()->create();
        ProformaItem::factory()->create([
            'proforma_id' => $proforma->id,
        ]);

        $this->actingAs($user)->get(route('proformas.pdf', $proforma))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_validated_proforma_can_be_converted_to_delivery_note_from_http_route(): void
    {
        $this->createSequence('delivery_note', 'BL');

        $user = $this->userWithPermissions([
            'proformas.convert_to_delivery_note',
        ]);

        $proforma = Proforma::factory()->validated()->create([
            'created_by' => User::factory()->create()->id,
        ]);
        $product = Product::factory()->create();

        ProformaItem::factory()->create([
            'proforma_id' => $proforma->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
        ]);

        $this->actingAs($user)->post(route('proformas.convert-to-delivery-note', $proforma))
            ->assertRedirect(route('proformas.show', $proforma));

        $deliveryNote = DeliveryNote::query()->firstOrFail();

        $this->assertSame('BL-'.now()->format('Y').'-00001', $deliveryNote->number);
        $this->assertSame($deliveryNote->id, $proforma->refresh()->deliveryNote->id);
        $this->assertSame(DocumentStatus::Converted, $proforma->status);
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
