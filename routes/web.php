<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\DeliveryNoteValidationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseValidationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceValidationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentValidationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProformaController;
use App\Http\Controllers\ProformaValidationController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidationCenterController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/logo.png', function () {
    $logoPath = public_path('logo.png');

    return Response::file(file_exists($logoPath) ? $logoPath : public_path('logo.png'));
})->name('brand.logo');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

Route::post('/logout', [LogoutController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
Route::middleware(['auth'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    Route::get('/validations', [ValidationCenterController::class, 'index'])
        ->name('validations.index')
        ->middleware('permission:validations.view');

    Route::get('/validations/history', [ValidationCenterController::class, 'history'])
        ->name('validations.history')
        ->middleware('permission:validations.view');

    Route::post('/validations/{type}/{id}/validate', [ValidationCenterController::class, 'validateItem'])
        ->name('validations.validate')
        ->middleware('permission:validations.validate|proformas.validate|delivery_notes.validate|invoices.validate|payments.validate|expenses.validate|stock.validate_movement');

    Route::post('/validations/{type}/{id}/reject', [ValidationCenterController::class, 'rejectItem'])
        ->name('validations.reject')
        ->middleware('permission:validations.reject|proformas.reject|delivery_notes.reject|invoices.reject|payments.reject|expenses.reject|stock.validate_movement');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index')
        ->middleware('permission:activity_logs.view');

    Route::get('/activity-logs/export/csv', [ActivityLogController::class, 'csv'])
        ->name('activity-logs.csv')
        ->middleware('permission:activity_logs.export|activity_logs.view');

    Route::get('/activity-logs/export/pdf', [ActivityLogController::class, 'pdf'])
        ->name('activity-logs.pdf')
        ->middleware('permission:activity_logs.export|activity_logs.view');

    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->name('activity-logs.show')
        ->middleware('permission:activity_logs.view');

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index')
        ->middleware('permission:reports.view_sales|reports.view_finance|reports.view_stock|reports.view_expenses');

    Route::get('/reports/pdf', [ReportController::class, 'pdf'])
        ->name('reports.pdf')
        ->middleware('permission:reports.export_pdf');

    Route::get('/reports/unpaid-invoices/pdf', [ReportController::class, 'unpaidInvoicesPdf'])
        ->name('reports.unpaid-invoices.pdf')
        ->middleware('permission:reports.export_pdf');

    Route::get('/reports/excel', [ReportController::class, 'excel'])
        ->name('reports.excel')
        ->middleware('permission:reports.export_excel');

    Route::get('/settings', [SettingController::class, 'index'])
        ->name('settings.index')
        ->middleware('permission:settings.view');

    Route::put('/settings', [SettingController::class, 'update'])
        ->name('settings.update')
        ->middleware('permission:settings.update_company|settings.update_numbering|settings.update_stock_rules');

    Route::post('/settings/payment-modes', [SettingController::class, 'storePaymentMode'])
        ->name('settings.payment-modes.store')
        ->middleware('permission:settings.update_payment_modes');

    Route::post('/settings/payment-modes/{paymentMode}/toggle', [SettingController::class, 'togglePaymentMode'])
        ->name('settings.payment-modes.toggle')
        ->middleware('permission:settings.update_payment_modes');

    Route::post('/settings/measurement-units', [SettingController::class, 'storeMeasurementUnit'])
        ->name('settings.measurement-units.store')
        ->middleware('permission:settings.update_units');

    Route::post('/settings/measurement-units/{measurementUnit}/toggle', [SettingController::class, 'toggleMeasurementUnit'])
        ->name('settings.measurement-units.toggle')
        ->middleware('permission:settings.update_units');

    Route::resource('clients', ClientController::class)
        ->middleware('permission:clients.view|clients.create|clients.update|clients.delete');

    Route::resource('product-categories', ProductCategoryController::class)
        ->except(['show'])
        ->middleware('permission:products.view|products.create|products.update|products.delete');

    Route::resource('products', ProductController::class)
        ->middleware('permission:products.view|products.create|products.update|products.delete');

    Route::post('/products/{product}/client-references', [ProductController::class, 'storeClientReference'])
        ->name('products.client-references.store')
        ->middleware('permission:products.update');

    Route::delete('/products/{product}/client-references/{clientProductPrice}', [ProductController::class, 'destroyClientReference'])
        ->name('products.client-references.destroy')
        ->middleware('permission:products.update');

    Route::resource('suppliers', SupplierController::class)
        ->middleware('permission:suppliers.view|suppliers.create|suppliers.update|suppliers.delete|suppliers.manage_products');

    Route::get('/purchases', [PurchaseController::class, 'index'])
        ->name('purchases.index')
        ->middleware('permission:purchases.view');

    Route::get('/purchases/requests/create', [PurchaseController::class, 'createRequest'])
        ->name('purchases.requests.create')
        ->middleware('permission:purchases.create_request');

    Route::post('/purchases/requests', [PurchaseController::class, 'storeRequest'])
        ->name('purchases.requests.store')
        ->middleware('permission:purchases.create_request');

    Route::get('/purchases/orders/create', [PurchaseController::class, 'createOrder'])
        ->name('purchases.orders.create')
        ->middleware('permission:purchases.create_order');

    Route::post('/purchases/orders', [PurchaseController::class, 'storeOrder'])
        ->name('purchases.orders.store')
        ->middleware('permission:purchases.create_order');

    Route::get('/purchases/orders/{order}', [PurchaseController::class, 'showOrder'])
        ->name('purchases.orders.show')
        ->middleware('permission:purchases.view');

    Route::get('/purchases/orders/{order}/pdf', [PurchaseController::class, 'orderPdf'])
        ->name('purchases.orders.pdf')
        ->middleware('permission:purchases.export_pdf');

    Route::get('/purchases/invoices/create', [PurchaseController::class, 'createInvoice'])
        ->name('purchases.invoices.create')
        ->middleware('permission:purchases.receive_invoice');

    Route::post('/purchases/invoices', [PurchaseController::class, 'storeInvoice'])
        ->name('purchases.invoices.store')
        ->middleware('permission:purchases.receive_invoice');

    Route::get('/purchases/payments/create', [PurchaseController::class, 'createPayment'])
        ->name('purchases.payments.create')
        ->middleware('permission:purchases.pay_supplier');

    Route::post('/purchases/payments', [PurchaseController::class, 'storePayment'])
        ->name('purchases.payments.store')
        ->middleware('permission:purchases.pay_supplier');

    Route::get('/treasury', [TreasuryController::class, 'index'])
        ->name('treasury.index')
        ->middleware('permission:treasury.view');

    Route::post('/treasury/expenses', [TreasuryController::class, 'storeExpense'])
        ->name('treasury.expenses.store')
        ->middleware('permission:treasury.create_expense');

    Route::get('/stock/physical', [StockController::class, 'physical'])
        ->name('stock.physical')
        ->middleware('permission:stock.view|stock.view_physical');

    Route::get('/stock/reserved', [StockController::class, 'reserved'])
        ->name('stock.reserved')
        ->middleware('permission:stock.view|stock.view_reserved');

    Route::get('/stock/suspense', [StockController::class, 'suspense'])
        ->name('stock.suspense')
        ->middleware('permission:stock.view|stock.view_suspense');

    Route::get('/stock/tool', [StockController::class, 'tool'])
        ->name('stock.tool')
        ->middleware('permission:stock.view|stock.view_tool');

    Route::get('/stock/movements', [StockController::class, 'movements'])
        ->name('stock.movements')
        ->middleware('permission:stock.view');

    Route::get('/stock/entries/create', [StockController::class, 'createEntry'])
        ->name('stock.entries.create')
        ->middleware('permission:stock.create_entry');

    Route::get('/stock/exits/create', [StockController::class, 'createExit'])
        ->name('stock.exits.create')
        ->middleware('permission:stock.create_exit');

    Route::get('/stock/adjustments/create', [StockController::class, 'createAdjustment'])
        ->name('stock.adjustments.create')
        ->middleware('permission:stock.adjust');

    Route::post('/stock/movements', [StockController::class, 'store'])
        ->name('stock.movements.store')
        ->middleware('permission:stock.create_entry|stock.create_exit|stock.adjust');

    Route::post('/stock/movements/{stockMovement}/validate', [StockController::class, 'validateMovement'])
        ->name('stock.movements.validate')
        ->middleware('permission:stock.validate_movement');

    Route::get('/stock/reports/low-stock', [StockController::class, 'lowStock'])
        ->name('stock.reports.low-stock')
        ->middleware('permission:stock.view');

    Route::get('/stock/reports/pdf', [StockController::class, 'reportPdf'])
        ->name('stock.reports.pdf')
        ->middleware('permission:stock.export');

    Route::resource('proformas', ProformaController::class)
        ->middleware('permission:proformas.view|proformas.create|proformas.update|proformas.delete_draft');

    Route::resource('customer-orders', CustomerOrderController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('permission:proformas.view|proformas.create|delivery_notes.create|invoices.create');

    Route::post('/customer-orders/{customerOrder}/convert-to-delivery-note', [CustomerOrderController::class, 'convertToDeliveryNote'])
        ->name('customer-orders.convert-to-delivery-note')
        ->middleware('permission:delivery_notes.create');

    Route::post('/customer-orders/{customerOrder}/convert-to-invoice', [CustomerOrderController::class, 'convertToInvoice'])
        ->name('customer-orders.convert-to-invoice')
        ->middleware('permission:invoices.create');

    Route::get('/proformas/{proforma}/pdf', [ProformaController::class, 'pdf'])
        ->name('proformas.pdf')
        ->middleware('permission:proformas.export_pdf');

    Route::post('/proformas/{proforma}/submit', [ProformaValidationController::class, 'submit'])
        ->name('proformas.submit')
        ->middleware('permission:proformas.submit');

    Route::post('/proformas/{proforma}/validate', [ProformaValidationController::class, 'validate'])
        ->name('proformas.validate')
        ->middleware('permission:proformas.validate');

    Route::post('/proformas/{proforma}/reject', [ProformaValidationController::class, 'reject'])
        ->name('proformas.reject')
        ->middleware('permission:proformas.reject');

    Route::post('/proformas/{proforma}/convert-to-delivery-note', [ProformaValidationController::class, 'convertToDeliveryNote'])
        ->name('proformas.convert-to-delivery-note')
        ->middleware('permission:proformas.convert_to_delivery_note');

    Route::post('/proformas/{proforma}/convert-to-invoice', [InvoiceController::class, 'storeFromProforma'])
        ->name('proformas.convert-to-invoice')
        ->middleware('permission:invoices.create');

    Route::resource('delivery-notes', DeliveryNoteController::class)
        ->middleware('permission:delivery_notes.view|delivery_notes.create|delivery_notes.update|delivery_notes.delete_draft');

    Route::get('/delivery-notes/{deliveryNote}/pdf', [DeliveryNoteController::class, 'pdf'])
        ->name('delivery-notes.pdf')
        ->middleware('permission:delivery_notes.export_pdf');

    Route::post('/delivery-notes/{deliveryNote}/submit', [DeliveryNoteValidationController::class, 'submit'])
        ->name('delivery-notes.submit')
        ->middleware('permission:delivery_notes.submit');

    Route::post('/delivery-notes/{deliveryNote}/validate', [DeliveryNoteValidationController::class, 'validate'])
        ->name('delivery-notes.validate')
        ->middleware('permission:delivery_notes.validate');

    Route::post('/delivery-notes/{deliveryNote}/reject', [DeliveryNoteValidationController::class, 'reject'])
        ->name('delivery-notes.reject')
        ->middleware('permission:delivery_notes.reject');

    Route::post('/delivery-notes/{deliveryNote}/mark-prepared', [DeliveryNoteValidationController::class, 'markPrepared'])
        ->name('delivery-notes.mark-prepared')
        ->middleware('permission:delivery_notes.mark_prepared');

    Route::post('/delivery-notes/{deliveryNote}/mark-delivered', [DeliveryNoteValidationController::class, 'markDelivered'])
        ->name('delivery-notes.mark-delivered')
        ->middleware('permission:delivery_notes.mark_delivered');

    Route::resource('invoices', InvoiceController::class)
        ->except(['destroy'])
        ->middleware('permission:invoices.view|invoices.create|invoices.update');

    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoices.pdf')
        ->middleware('permission:invoices.export_pdf');

    Route::post('/invoices/{invoice}/submit', [InvoiceValidationController::class, 'submit'])
        ->name('invoices.submit')
        ->middleware('permission:invoices.submit');

    Route::post('/invoices/{invoice}/validate', [InvoiceValidationController::class, 'validate'])
        ->name('invoices.validate')
        ->middleware('permission:invoices.validate');

    Route::post('/invoices/{invoice}/reject', [InvoiceValidationController::class, 'reject'])
        ->name('invoices.reject')
        ->middleware('permission:invoices.reject');

    Route::get('/payments/cash-journal', [PaymentController::class, 'cashJournal'])
        ->name('payments.cash-journal')
        ->middleware('permission:payments.view|payments.view_daily_cash');

    Route::resource('payments', PaymentController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('permission:payments.view|payments.create');

    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
        ->name('payments.receipt')
        ->middleware('permission:payments.export_receipt_pdf');

    Route::post('/payments/{payment}/submit', [PaymentValidationController::class, 'submit'])
        ->name('payments.submit')
        ->middleware('permission:payments.submit');

    Route::post('/payments/{payment}/validate', [PaymentValidationController::class, 'validate'])
        ->name('payments.validate')
        ->middleware('permission:payments.validate');

    Route::post('/payments/{payment}/reject', [PaymentValidationController::class, 'reject'])
        ->name('payments.reject')
        ->middleware('permission:payments.reject');

    Route::resource('expenses', ExpenseController::class)
        ->except(['destroy'])
        ->middleware('permission:expenses.view|expenses.create|expenses.update');

    Route::resource('expense-categories', ExpenseCategoryController::class)
        ->except(['show'])
        ->middleware('permission:expense_categories.view|expense_categories.create|expense_categories.update|expense_categories.delete|expense_categories.disable');

    Route::get('/expense-categories/{expense_category}', [ExpenseCategoryController::class, 'show'])
        ->name('expense-categories.show')
        ->middleware('permission:expense_categories.view');

    Route::get('/expenses-export/pdf', [ExpenseController::class, 'pdf'])
        ->name('expenses.pdf')
        ->middleware('permission:expenses.export');

    Route::post('/expenses/{expense}/submit', [ExpenseValidationController::class, 'submit'])
        ->name('expenses.submit')
        ->middleware('permission:expenses.submit');

    Route::post('/expenses/{expense}/validate', [ExpenseValidationController::class, 'validate'])
        ->name('expenses.validate')
        ->middleware('permission:expenses.validate');

    Route::post('/expenses/{expense}/reject', [ExpenseValidationController::class, 'reject'])
        ->name('expenses.reject')
        ->middleware('permission:expenses.reject');

    Route::resource('users', UserController::class)
        ->middleware('permission:users.view|users.create|users.update|users.delete|users.disable|users.reset_password|users.assign_roles|users.assign_permissions');

    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggle'])
        ->name('users.toggle')
        ->middleware('permission:users.disable');

    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.reset-password')
        ->middleware('permission:users.reset_password');

    Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])
        ->name('users.permissions')
        ->middleware('permission:users.assign_permissions|sensitive.modify_roles_permissions');

    Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions'])
        ->name('users.permissions.update')
        ->middleware('permission:users.assign_permissions|sensitive.modify_roles_permissions');

    Route::resource('roles', RoleController::class)
        ->middleware('permission:roles.view|roles.create|roles.update|roles.delete|roles.assign_permissions');

    Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions'])
        ->name('roles.permissions')
        ->middleware('permission:roles.assign_permissions|sensitive.modify_roles_permissions');

    Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
        ->name('roles.permissions.update')
        ->middleware('permission:roles.assign_permissions|sensitive.modify_roles_permissions');

});
