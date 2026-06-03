<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'company.name',
                'value' => 'SFMID',
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],
            [
                'key' => 'company.full_name',
                'value' => 'Société de Fourniture de Matériel Industriel et Divers',
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],
            [
                'key' => 'company.phone',
                'value' => null,
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],
            [
                'key' => 'company.email',
                'value' => null,
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],
            [
                'key' => 'company.address',
                'value' => 'Burkina Faso',
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],
            [
                'key' => 'company.ifu',
                'value' => null,
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],
            [
                'key' => 'company.rccm',
                'value' => null,
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],
            [
                'key' => 'company.logo_path',
                'value' => null,
                'type' => 'string',
                'group' => 'company',
                'is_public' => true,
            ],

            [
                'key' => 'sales.default_tax_rate',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'sales',
                'is_public' => false,
            ],
            [
                'key' => 'sales.currency',
                'value' => 'FCFA',
                'type' => 'string',
                'group' => 'sales',
                'is_public' => true,
            ],
            [
                'key' => 'sales.default_payment_delay_days',
                'value' => '0',
                'type' => 'integer',
                'group' => 'sales',
                'is_public' => false,
            ],
            [
                'key' => 'stock.reserve_on_proforma',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'stock',
                'is_public' => false,
            ],
            [
                'key' => 'stock.allow_negative_stock',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'stock',
                'is_public' => false,
            ],
            [
                'key' => 'stock.low_stock_alert_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'stock',
                'is_public' => false,
            ],
            [
                'key' => 'pdf.footer_note',
                'value' => 'Merci pour votre confiance.',
                'type' => 'string',
                'group' => 'pdf',
                'is_public' => true,
            ],
            [
                'key' => 'pdf.signature_left',
                'value' => 'SFMID',
                'type' => 'string',
                'group' => 'pdf',
                'is_public' => true,
            ],
            [
                'key' => 'pdf.signature_right',
                'value' => 'Client',
                'type' => 'string',
                'group' => 'pdf',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            CompanySetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'is_public' => $setting['is_public'],
                ]
            );
        }
    }
}
