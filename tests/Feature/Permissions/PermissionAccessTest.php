<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_role_permission_can_access_protected_route(): void
    {
        $this->defineProtectedRoute();

        $permission = $this->createPermission('test.access');

        $role = Role::factory()->create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/_test/permission-access')
            ->assertOk()
            ->assertSee('allowed');
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->defineProtectedRoute();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/_test/permission-access')
            ->assertForbidden();
    }

    public function test_user_permission_override_can_grant_permission(): void
    {
        $this->defineProtectedRoute();

        $permission = $this->createPermission('test.access');

        $user = User::factory()->create();

        UserPermissionOverride::query()->create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'is_allowed' => true,
            'created_by' => null,
            'reason' => 'Autorisation spéciale de test.',
        ]);

        $this->actingAs($user)
            ->get('/_test/permission-access')
            ->assertOk()
            ->assertSee('allowed');
    }

    public function test_user_permission_override_can_deny_role_permission(): void
    {
        $this->defineProtectedRoute();

        $permission = $this->createPermission('test.access');

        $role = Role::factory()->create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        UserPermissionOverride::query()->create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'is_allowed' => false,
            'created_by' => null,
            'reason' => 'Restriction spéciale de test.',
        ]);

        $this->actingAs($user)
            ->get('/_test/permission-access')
            ->assertForbidden();
    }

    public function test_super_admin_role_can_access_any_permission(): void
    {
        $this->defineProtectedRoute();

        $role = Role::factory()->create([
            'name' => 'Super administrateur',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/_test/permission-access')
            ->assertOk()
            ->assertSee('allowed');
    }

    private function defineProtectedRoute(): void
    {
        Route::middleware(['web', 'auth', 'permission:test.access'])
            ->get('/_test/permission-access', fn (): string => 'allowed');
    }

    private function createPermission(string $slug): Permission
    {
        return Permission::query()->create([
            'name' => 'Test Access',
            'slug' => $slug,
            'module' => 'test',
            'action' => 'access',
            'is_sensitive' => false,
            'description' => null,
        ]);
    }
}
