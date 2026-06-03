<?php

namespace Tests\Feature\Settings;

use App\Models\CompanySetting;
use App\Models\DocumentNumberSequence;
use App\Models\MeasurementUnit;
use App\Models\PaymentMode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\DocumentNumberSequenceSeeder;
use Database\Seeders\MeasurementUnitSeeder;
use Database\Seeders\PaymentModeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_updates_company_sales_stock_and_numbering(): void
    {
        $this->seedBaseData();
        $user = $this->userWithPermissions([
            'settings.view',
            'settings.update_company',
            'settings.update_numbering',
            'settings.update_stock_rules',
        ]);

        $sequence = DocumentNumberSequence::query()->where('document_type', 'invoice')->firstOrFail();

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Informations societe');

        $this->actingAs($user)->put(route('settings.update'), [
            'company' => [
                'name' => 'SFMID Test',
                'full_name' => 'Societe Test',
                'phone' => '70000000',
                'email' => 'contact@sfmid.test',
                'address' => 'Ouagadougou',
                'ifu' => 'IFU-TEST',
                'rccm' => 'RCCM-TEST',
            ],
            'sales' => [
                'currency' => 'XOF',
                'default_payment_delay_days' => 30,
                'default_tax_rate' => 18,
            ],
            'stock' => [
                'reserve_on_proforma' => 1,
                'allow_negative_stock' => 0,
                'low_stock_alert_enabled' => 1,
            ],
            'pdf' => [
                'footer_note' => 'Pied de page test.',
                'signature_left' => 'Direction',
                'signature_right' => 'Client',
            ],
            'sequences' => [
                $sequence->id => [
                    'prefix' => 'INV',
                    'next_number' => 25,
                    'padding' => 6,
                    'reset_period' => 'yearly',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas(CompanySetting::class, ['key' => 'company.name', 'value' => 'SFMID Test']);
        $this->assertDatabaseHas(CompanySetting::class, ['key' => 'sales.currency', 'value' => 'XOF']);
        $this->assertDatabaseHas(DocumentNumberSequence::class, ['id' => $sequence->id, 'prefix' => 'INV', 'next_number' => 25, 'padding' => 6]);
    }

    public function test_payment_modes_and_units_can_be_added_and_toggled(): void
    {
        $this->seedBaseData();
        $user = $this->userWithPermissions([
            'settings.view',
            'settings.update_payment_modes',
            'settings.update_units',
        ]);

        $this->actingAs($user)->post(route('settings.payment-modes.store'), [
            'name' => 'Orange Money',
            'code' => 'orange_money',
            'is_active' => 1,
        ])->assertRedirect();

        $mode = PaymentMode::query()->where('code', 'orange_money')->firstOrFail();
        $this->actingAs($user)->post(route('settings.payment-modes.toggle', $mode))->assertRedirect();
        $this->assertFalse($mode->fresh()->is_active);

        $this->actingAs($user)->post(route('settings.measurement-units.store'), [
            'name' => 'Palette',
            'code' => 'palette',
            'is_active' => 1,
        ])->assertRedirect();

        $unit = MeasurementUnit::query()->where('code', 'palette')->firstOrFail();
        $this->actingAs($user)->post(route('settings.measurement-units.toggle', $unit))->assertRedirect();
        $this->assertFalse($unit->fresh()->is_active);
    }

    private function seedBaseData(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(CompanySettingSeeder::class);
        $this->seed(DocumentNumberSequenceSeeder::class);
        $this->seed(PaymentModeSeeder::class);
        $this->seed(MeasurementUnitSeeder::class);
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
