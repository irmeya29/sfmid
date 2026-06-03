<?php

namespace Tests\Feature\Permissions;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_crud_status_reset_and_role_assignment_are_available(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = $this->userWithPermissions([
            'users.view',
            'users.create',
            'users.update',
            'users.disable',
            'users.reset_password',
            'users.assign_roles',
        ]);

        $assignedRole = Role::factory()->create(['name' => 'Commercial test', 'slug' => 'commercial-test']);

        $response = $this->actingAs($actor)->post(route('users.store'), [
            'name' => 'Awa Traore',
            'email' => 'awa@example.test',
            'phone' => '70000000',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'is_active' => '1',
            'role_ids' => [$assignedRole->id],
        ]);

        $user = User::query()->where('email', 'awa@example.test')->firstOrFail();

        $response->assertRedirect(route('users.show', $user));
        $this->assertTrue($user->roles()->whereKey($assignedRole->id)->exists());

        $this->actingAs($actor)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Awa Traore');

        $this->actingAs($actor)
            ->post(route('users.toggle', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($actor)->post(route('users.reset-password', $user), [
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NewPassword@123', $user->fresh()->password));
    }

    public function test_role_crud_and_permission_assignment_are_logged(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = $this->userWithPermissions([
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign_permissions',
        ]);

        $permission = Permission::query()->where('slug', 'clients.view')->firstOrFail();

        $response = $this->actingAs($actor)->post(route('roles.store'), [
            'name' => 'Role test dynamique',
            'slug' => 'role-test-dynamique',
            'description' => 'Role cree pendant le test.',
            'is_active' => '1',
            'permission_ids' => [$permission->id],
        ]);

        $role = Role::query()->where('slug', 'role-test-dynamique')->firstOrFail();

        $response->assertRedirect(route('roles.show', $role));
        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());

        $this->actingAs($actor)
            ->get(route('roles.permissions', $role))
            ->assertOk()
            ->assertSee('Clients')
            ->assertSee('Consulter')
            ->assertDontSee('clients.view');

        $stockPermission = Permission::query()->where('slug', 'stock.view')->firstOrFail();

        $this->actingAs($actor)
            ->put(route('roles.permissions.update', $role), [
                'permission_ids' => [$stockPermission->id],
            ])
            ->assertRedirect(route('roles.show', $role));

        $this->assertTrue($role->fresh()->permissions()->whereKey($stockPermission->id)->exists());
        $this->assertDatabaseHas(ActivityLog::class, [
            'action' => 'permissions_synced',
            'module' => 'permissions',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
        ]);
    }

    public function test_user_permission_override_changes_effective_access(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->defineProtectedRoute();

        $actor = $this->userWithPermissions([
            'users.view',
            'users.assign_permissions',
        ]);

        $target = User::factory()->create();
        $permission = Permission::query()->where('slug', 'clients.view')->firstOrFail();

        $this->actingAs($actor)->put(route('users.permissions.update', $target), [
            'overrides' => [$permission->id => 'allow'],
            'reason' => 'Acces temporaire.',
        ])->assertRedirect(route('users.show', $target));

        $this->actingAs($target)->get('/_test/clients-view')->assertOk();

        $this->actingAs($actor)->put(route('users.permissions.update', $target), [
            'overrides' => [$permission->id => 'deny'],
            'reason' => 'Retrait temporaire.',
        ])->assertRedirect(route('users.show', $target));

        $this->actingAs($target->fresh())->get('/_test/clients-view')->assertForbidden();
    }

    public function test_super_admin_role_cannot_be_assigned_without_sensitive_permission(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = $this->userWithPermissions([
            'users.create',
            'users.assign_roles',
        ]);

        $superAdminRole = Role::factory()->create([
            'name' => 'Super administrateur',
            'slug' => 'super-admin',
        ]);

        $this->actingAs($actor)->post(route('users.store'), [
            'name' => 'Root Test',
            'email' => 'root@example.test',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'is_active' => '1',
            'role_ids' => [$superAdminRole->id],
        ])->assertForbidden();

        $actor->roles()->first()->permissions()->attach(
            Permission::query()->where('slug', 'sensitive.create_super_admin')->value('id')
        );

        $this->actingAs($actor->fresh())->post(route('users.store'), [
            'name' => 'Root Test',
            'email' => 'root@example.test',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'is_active' => '1',
            'role_ids' => [$superAdminRole->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'root@example.test']);
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::factory()->create([
            'name' => 'Actor role '.uniqid(),
            'slug' => 'actor-role-'.uniqid(),
        ]);

        $role->permissions()->attach(
            Permission::query()->whereIn('slug', $slugs)->pluck('id')->all()
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function defineProtectedRoute(): void
    {
        Route::middleware(['web', 'auth', 'permission:clients.view'])
            ->get('/_test/clients-view', fn (): string => 'allowed');
    }
}
