<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super administrateur',
                'slug' => 'super-admin',
                'description' => 'Accès total au système.',
                'is_system' => true,
                'permissions' => ['*'],
            ],
            [
                'name' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Administration, supervision, utilisateurs, rôles, validations et paramètres.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.*',
                    'validations.*',
                    'users.*',
                    'roles.*',
                    'permissions.*',
                    'clients.*',
                    'products.*',
                    'suppliers.*',
                    'purchases.*',
                    'stock.*',
                    'proformas.*',
                    'delivery_notes.*',
                    'invoices.*',
                    'payments.*',
                    'treasury.*',
                    'expenses.*',
                    'expense_categories.*',
                    'reports.*',
                    'activity_logs.*',
                    'settings.*',
                    'sensitive.modify_roles_permissions',
                    'sensitive.access_activity_logs',
                ],
            ],
            [
                'name' => 'Responsable',
                'slug' => 'responsable',
                'description' => 'Contrôle et validation des opérations.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'validations.view',
                    'validations.validate',
                    'validations.reject',
                    'clients.view',
                    'products.view',
                    'suppliers.view',
                    'purchases.view',
                    'stock.view',
                    'stock.view_physical',
                    'stock.view_suspense',
                    'proformas.view',
                    'proformas.validate',
                    'proformas.reject',
                    'delivery_notes.view',
                    'delivery_notes.validate',
                    'delivery_notes.reject',
                    'delivery_notes.mark_delivered',
                    'invoices.view',
                    'invoices.validate',
                    'invoices.reject',
                    'payments.view',
                    'treasury.view',
                    'payments.validate',
                    'payments.reject',
                    'expenses.view',
                    'expenses.validate',
                    'expenses.reject',
                    'reports.view_sales',
                    'reports.view_stock',
                    'reports.view_expenses',
                ],
            ],
            [
                'name' => 'Direction',
                'slug' => 'direction',
                'description' => 'Consultation globale et rapports.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'validations.view',
                    'clients.view',
                    'clients.view_balance',
                    'clients.view_history',
                    'products.view',
                    'suppliers.view',
                    'purchases.view',
                    'stock.view',
                    'stock.view_physical',
                    'stock.view_reserved',
                    'stock.view_suspense',
                    'proformas.view',
                    'delivery_notes.view',
                    'invoices.view',
                    'invoices.view_unpaid',
                    'payments.view',
                    'treasury.view',
                    'expenses.view',
                    'reports.view_sales',
                    'reports.view_finance',
                    'reports.view_stock',
                    'reports.view_expenses',
                    'reports.export_pdf',
                    'reports.export_excel',
                ],
            ],
            [
                'name' => 'Comptable',
                'slug' => 'comptable',
                'description' => 'Gestion financière opérationnelle.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'validations.view',
                    'clients.view',
                    'clients.view_balance',
                    'products.view',
                    'suppliers.view',
                    'purchases.view',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'invoices.submit',
                    'invoices.correct_rejected',
                    'invoices.view_unpaid',
                    'invoices.export_pdf',
                    'payments.view',
                    'treasury.view',
                    'treasury.create_expense',
                    'payments.create',
                    'payments.submit',
                    'payments.export_receipt_pdf',
                    'expenses.view',
                    'expenses.create',
                    'expenses.update',
                    'expenses.submit',
                    'expenses.correct_rejected',
                    'reports.view_finance',
                ],
            ],
            [
                'name' => 'Caissier',
                'slug' => 'caissier',
                'description' => 'Encaissements et reçus.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'clients.view',
                    'invoices.view',
                    'invoices.view_unpaid',
                    'payments.view',
                    'payments.create',
                    'payments.submit',
                    'payments.export_receipt_pdf',
                    'payments.view_daily_cash',
                ],
            ],
            [
                'name' => 'Commercial',
                'slug' => 'commercial',
                'description' => 'Gestion commerciale, clients et proformas.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'clients.view',
                    'clients.create',
                    'clients.update',
                    'clients.view_history',
                    'products.view',
                    'suppliers.view',
                    'purchases.view',
                    'stock.view',
                    'stock.view_physical',
                    'proformas.view',
                    'proformas.create',
                    'proformas.update',
                    'proformas.submit',
                    'proformas.correct_rejected',
                    'proformas.export_pdf',
                ],
            ],
            [
                'name' => 'Responsable commercial',
                'slug' => 'responsable-commercial',
                'description' => 'Supervision commerciale et validation des proformas.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'clients.view',
                    'clients.create',
                    'clients.update',
                    'clients.view_balance',
                    'clients.view_history',
                    'products.view',
                    'suppliers.view',
                    'purchases.view',
                    'purchases.create_request',
                    'purchases.create_order',
                    'purchases.receive_invoice',
                    'purchases.export_pdf',
                    'stock.view',
                    'stock.view_physical',
                    'proformas.view',
                    'proformas.create',
                    'proformas.update',
                    'proformas.submit',
                    'proformas.validate',
                    'proformas.reject',
                    'proformas.cancel',
                    'proformas.convert_to_delivery_note',
                    'proformas.export_pdf',
                    'reports.view_sales',
                ],
            ],
            [
                'name' => 'Magasinier',
                'slug' => 'magasinier',
                'description' => 'Gestion opérationnelle du stock et préparation des livraisons.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'suppliers.view',
                    'suppliers.create',
                    'suppliers.update',
                    'suppliers.manage_products',
                    'purchases.view',
                    'purchases.create_request',
                    'purchases.create_order',
                    'purchases.receive_invoice',
                    'purchases.pay_supplier',
                    'purchases.export_pdf',
                    'stock.view',
                    'stock.view_physical',
                    'stock.view_reserved',
                    'stock.view_suspense',
                    'stock.create_entry',
                    'stock.create_exit',
                    'delivery_notes.view',
                    'delivery_notes.mark_prepared',
                ],
            ],
            [
                'name' => 'Responsable stock',
                'slug' => 'responsable-stock',
                'description' => 'Supervision stock, livraisons et mouvements sensibles.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'stock.view',
                    'stock.view_physical',
                    'stock.view_reserved',
                    'stock.view_suspense',
                    'stock.view_tool',
                    'stock.create_entry',
                    'stock.create_exit',
                    'stock.adjust',
                    'stock.validate_movement',
                    'stock.close_suspense',
                    'stock.export',
                    'delivery_notes.view',
                    'delivery_notes.validate',
                    'delivery_notes.reject',
                    'delivery_notes.mark_prepared',
                    'delivery_notes.mark_delivered',
                    'reports.view_stock',
                ],
            ],
            [
                'name' => 'RH',
                'slug' => 'rh',
                'description' => 'Saisie des charges liées au personnel.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'expenses.view',
                    'expenses.create',
                    'expenses.update',
                    'expenses.submit',
                    'expenses.correct_rejected',
                ],
            ],
            [
                'name' => 'Responsable RH',
                'slug' => 'responsable-rh',
                'description' => 'Validation et supervision des charges RH.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'expenses.view',
                    'expenses.create',
                    'expenses.update',
                    'expenses.submit',
                    'expenses.validate',
                    'expenses.reject',
                    'expenses.view_sensitive',
                    'reports.view_hr',
                    'sensitive.view_salaries',
                ],
            ],
            [
                'name' => 'Auditeur',
                'slug' => 'auditeur',
                'description' => 'Lecture seule et contrôle.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'clients.view',
                    'products.view',
                    'stock.view',
                    'stock.view_physical',
                    'stock.view_reserved',
                    'stock.view_suspense',
                    'proformas.view',
                    'delivery_notes.view',
                    'invoices.view',
                    'payments.view',
                    'expenses.view',
                    'reports.view_sales',
                    'reports.view_stock',
                    'activity_logs.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionPatterns = $roleData['permissions'];

            unset($roleData['permissions']);

            $role = Role::query()->updateOrCreate(
                ['slug' => $roleData['slug']],
                array_merge($roleData, ['is_active' => true])
            );

            $role->permissions()->sync(
                $this->resolvePermissionIds($permissionPatterns)
            );
        }
    }

    /**
     * @param  array<int, string>  $patterns
     * @return array<int, int>
     */
    private function resolvePermissionIds(array $patterns): array
    {
        if (in_array('*', $patterns, true)) {
            return Permission::query()->pluck('id')->all();
        }

        $ids = [];

        foreach ($patterns as $pattern) {
            if (Str::endsWith($pattern, '.*')) {
                $module = Str::before($pattern, '.*');

                $ids = array_merge(
                    $ids,
                    Permission::query()
                        ->where('module', $module)
                        ->pluck('id')
                        ->all()
                );

                continue;
            }

            $permissionId = Permission::query()
                ->where('slug', $pattern)
                ->value('id');

            if ($permissionId !== null) {
                $ids[] = (int) $permissionId;
            }
        }

        return array_values(array_unique($ids));
    }
}
