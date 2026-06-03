<?php

namespace Tests\Feature\Audit;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_logs_require_permission_and_can_be_filtered(): void
    {
        $this->seed(PermissionSeeder::class);

        $viewer = $this->userWithPermissions(['activity_logs.view']);
        $blocked = User::factory()->create(['is_active' => true]);

        $log = ActivityLog::query()->create([
            'user_id' => $viewer->id,
            'action' => 'created',
            'module' => 'clients',
            'description' => 'Client cree.',
            'old_values' => null,
            'new_values' => ['name' => 'Client Audit'],
        ]);

        $this->actingAs($blocked)->get(route('activity-logs.index'))->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('activity-logs.index', ['module' => 'clients', 'action' => 'created', 'user_id' => $viewer->id]))
            ->assertOk()
            ->assertSee('Client cree.');

        $this->actingAs($viewer)
            ->get(route('activity-logs.show', $log))
            ->assertOk()
            ->assertSee('Client Audit');
    }

    public function test_activity_logs_can_be_exported_to_csv_and_pdf(): void
    {
        $this->seed(PermissionSeeder::class);

        $viewer = $this->userWithPermissions(['activity_logs.view', 'activity_logs.export']);

        ActivityLog::query()->create([
            'user_id' => $viewer->id,
            'action' => 'updated',
            'module' => 'products',
            'description' => 'Produit modifie.',
            'old_values' => ['name' => 'Ancien'],
            'new_values' => ['name' => 'Nouveau'],
        ]);

        $this->actingAs($viewer)
            ->get(route('activity-logs.csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($viewer)
            ->get(route('activity-logs.pdf'))
            ->assertOk();
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::query()->whereIn('slug', $slugs)->pluck('id')->all());

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }
}
