<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard' => [
                'view',
            ],

            'validations' => [
                'view',
                'validate',
                'reject',
            ],

            'users' => [
                'view',
                'create',
                'update',
                'delete',
                'disable',
                'reset_password',
                'assign_roles',
                'assign_permissions',
            ],

            'roles' => [
                'view',
                'create',
                'update',
                'delete',
                'disable',
                'assign_permissions',
            ],

            'permissions' => [
                'view',
                'assign',
                'revoke',
            ],

            'clients' => [
                'view',
                'create',
                'update',
                'delete',
                'disable',
                'view_balance',
                'view_history',
                'export',
            ],

            'products' => [
                'view',
                'create',
                'update',
                'delete',
                'disable',
                'update_purchase_price',
                'update_sale_price',
                'update_client_price',
                'update_alert_threshold',
                'view_margin',
                'import',
                'export',
            ],

            'suppliers' => [
                'view',
                'create',
                'update',
                'delete',
                'manage_products',
            ],

            'purchases' => [
                'view',
                'create_request',
                'create_order',
                'receive_invoice',
                'pay_supplier',
                'export_pdf',
            ],

            'stock' => [
                'view',
                'view_physical',
                'view_reserved',
                'view_suspense',
                'view_tool',
                'create_entry',
                'create_exit',
                'adjust',
                'validate_movement',
                'cancel_movement',
                'close_suspense',
                'export',
            ],

            'proformas' => [
                'view',
                'create',
                'update',
                'delete_draft',
                'submit',
                'validate',
                'reject',
                'correct_rejected',
                'cancel',
                'convert_to_delivery_note',
                'export_pdf',
            ],

            'delivery_notes' => [
                'view',
                'create',
                'update',
                'delete_draft',
                'submit',
                'validate',
                'reject',
                'correct_rejected',
                'cancel',
                'mark_prepared',
                'mark_delivered',
                'convert_to_invoice',
                'export_pdf',
            ],

            'invoices' => [
                'view',
                'create',
                'update',
                'delete_draft',
                'submit',
                'validate',
                'reject',
                'correct_rejected',
                'cancel',
                'view_unpaid',
                'view_paid',
                'export_pdf',
            ],

            'payments' => [
                'view',
                'create',
                'submit',
                'validate',
                'reject',
                'cancel',
                'export_receipt_pdf',
                'view_daily_cash',
                'close_cash',
                'close_suspense_stock',
            ],

            'treasury' => [
                'view',
                'create_expense',
                'export',
            ],

            'expenses' => [
                'view',
                'create',
                'update',
                'delete_draft',
                'submit',
                'validate',
                'reject',
                'correct_rejected',
                'cancel',
                'view_sensitive',
                'export',
            ],

            'expense_categories' => [
                'view',
                'create',
                'update',
                'delete',
                'disable',
            ],

            'reports' => [
                'view_sales',
                'view_finance',
                'view_stock',
                'view_expenses',
                'view_hr',
                'export_pdf',
                'export_excel',
            ],

            'activity_logs' => [
                'view',
                'export',
            ],

            'settings' => [
                'view',
                'update_company',
                'update_numbering',
                'update_payment_modes',
                'update_units',
                'update_stock_rules',
            ],

            'sensitive' => [
                'validate_own_documents',
                'cancel_validated_invoice',
                'cancel_validated_payment',
                'cancel_delivered_delivery_note',
                'update_validated_document',
                'create_super_admin',
                'view_salaries',
                'export_financial_reports',
                'modify_roles_permissions',
                'access_activity_logs',
            ],
        ];

        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";

                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $this->label($module, $action),
                        'module' => $module,
                        'action' => $action,
                        'is_sensitive' => $this->isSensitive($module, $action),
                        'description' => null,
                    ]
                );
            }
        }
    }

    private function label(string $module, string $action): string
    {
        $moduleLabel = Str::of($module)->replace('_', ' ')->title()->toString();
        $actionLabel = Str::of($action)->replace('_', ' ')->title()->toString();

        return "{$moduleLabel} - {$actionLabel}";
    }

    private function isSensitive(string $module, string $action): bool
    {
        return $module === 'sensitive'
            || in_array($action, [
                'delete',
                'disable',
                'validate',
                'reject',
                'cancel',
                'validate',
                'adjust',
                'validate_movement',
                'cancel_movement',
                'close_suspense',
                'close_suspense_stock',
                'close_cash',
                'update_purchase_price',
                'update_client_price',
                'view_margin',
                'view_sensitive',
                'view_hr',
                'export_financial_reports',
                'assign_permissions',
                'assign_roles',
            ], true);
    }
}
