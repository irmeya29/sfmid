<?php

namespace Tests\Feature\Stock;

use App\Models\Permission;
use App\Models\Role;
use App\Models\StockSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockSiteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_site_page_creates_and_updates_sales_site(): void
    {
        $user = $this->userWithPermissions(['stock.manage_sites']);

        $this->actingAs($user)
            ->get(route('stock-sites.index'))
            ->assertOk()
            ->assertSee('Sites de stock')
            ->assertSee('Nouveau site');

        $this->actingAs($user)
            ->post(route('stock-sites.store'), [
                'name' => 'Ouaga vente',
                'code' => 'OUAGA-VENTE',
                'description' => 'Site de vente Ouagadougou',
                'can_store' => '1',
                'can_sell' => '1',
                'is_active' => '1',
                'is_default' => '0',
            ])
            ->assertRedirect(route('stock-sites.index'));

        $site = StockSite::query()->where('code', 'OUAGA-VENTE')->firstOrFail();

        $this->assertTrue($site->can_store);
        $this->assertTrue($site->can_sell);
        $this->assertTrue($site->is_active);

        $this->actingAs($user)
            ->get(route('stock-sites.edit', $site))
            ->assertOk()
            ->assertSee('Ouaga vente');
    }

    public function test_last_active_sales_site_cannot_be_disabled(): void
    {
        $user = $this->userWithPermissions(['stock.manage_sites']);

        StockSite::query()->where('can_sell', true)->delete();

        $site = StockSite::query()->create([
            'name' => 'Vente unique',
            'code' => 'VENTE-UNIQUE',
            'can_store' => true,
            'can_sell' => true,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('stock-sites.toggle', $site))
            ->assertSessionHasErrors('can_sell');

        $this->assertTrue($site->refresh()->is_active);
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
