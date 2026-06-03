<?php

namespace Tests\Feature\Clients;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_clients_with_permission(): void
    {
        $user = $this->userWithPermissions(['clients.view']);

        $client = Client::factory()->create([
            'name' => 'Mine Essakane',
            'code' => 'CLI-TEST-001',
        ]);

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Mine Essakane')
            ->assertSee('CLI-TEST-001');
    }

    public function test_user_can_create_client_with_permission(): void
    {
        $user = $this->userWithPermissions([
            'clients.create',
            'clients.view',
        ]);

        $response = $this->actingAs($user)
            ->post(route('clients.store'), [
                'code' => 'CLI-001',
                'name' => 'Client industriel test',
                'type' => ClientType::Industry->value,
                'phone' => '70000000',
                'email' => 'client@example.com',
                'address' => 'Ouagadougou',
                'ifu' => '1234567A',
                'rccm' => 'RCCM-BF-001',
                'payment_delay_days' => 30,
                'commercial_terms' => 'Paiement à 30 jours.',
                'status' => ClientStatus::Active->value,
            ]);

        $client = Client::query()->where('code', 'CLI-001')->firstOrFail();

        $response->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('clients', [
            'code' => 'CLI-001',
            'name' => 'Client industriel test',
            'type' => ClientType::Industry->value,
            'status' => ClientStatus::Active->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Client::class,
            'subject_id' => $client->id,
            'action' => 'created',
            'module' => 'clients',
        ]);
    }

    public function test_client_creation_requires_name(): void
    {
        $user = $this->userWithPermissions(['clients.create']);

        $this->actingAs($user)
            ->from(route('clients.create'))
            ->post(route('clients.store'), [
                'code' => 'CLI-002',
                'name' => '',
                'type' => ClientType::Industry->value,
                'payment_delay_days' => 30,
                'status' => ClientStatus::Active->value,
            ])
            ->assertRedirect(route('clients.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_user_can_update_client_with_permission(): void
    {
        $user = $this->userWithPermissions([
            'clients.update',
            'clients.view',
        ]);

        $client = Client::factory()->create([
            'code' => 'CLI-003',
            'name' => 'Ancien nom',
        ]);

        $response = $this->actingAs($user)
            ->put(route('clients.update', $client), [
                'code' => 'CLI-003',
                'name' => 'Nouveau nom',
                'type' => ClientType::Mine->value,
                'phone' => '71000000',
                'email' => 'new@example.com',
                'address' => 'Bobo-Dioulasso',
                'ifu' => '7654321B',
                'rccm' => 'RCCM-BF-002',
                'payment_delay_days' => 15,
                'commercial_terms' => 'Paiement à 15 jours.',
                'status' => ClientStatus::Active->value,
            ]);

        $response->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Nouveau nom',
            'type' => ClientType::Mine->value,
            'phone' => '71000000',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Client::class,
            'subject_id' => $client->id,
            'action' => 'updated',
            'module' => 'clients',
        ]);
    }

    public function test_user_can_soft_delete_client_with_permission(): void
    {
        $user = $this->userWithPermissions([
            'clients.delete',
            'clients.view',
        ]);

        $client = Client::factory()->create();

        $this->actingAs($user)
            ->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Client::class,
            'subject_id' => $client->id,
            'action' => 'deleted',
            'module' => 'clients',
        ]);
    }

    public function test_user_without_permission_cannot_access_clients(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::factory()->create();

        foreach ($permissions as $slug) {
            $permission = Permission::query()->firstOrCreate(
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
