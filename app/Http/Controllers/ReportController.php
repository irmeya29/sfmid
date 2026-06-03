<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockSuspense;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', [
            'report' => $this->build($request),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'expenseCategories' => ExpenseCategory::query()->orderBy('name')->get(['id', 'name']),
            'productCategories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $this->filters($request),
            'canViewMargin' => $request->user()->hasPermission('products.view_margin'),
        ]);
    }

    public function pdf(Request $request): Response
    {
        return Pdf::loadView('reports.pdf', [
            'report' => $this->build($request),
            'filters' => $this->filters($request),
            'canViewMargin' => $request->user()->hasPermission('products.view_margin'),
            'company' => CompanySetting::query()->pluck('value', 'key')->all(),
        ])->setPaper('a4', 'landscape')->stream('rapport-statistiques.pdf');
    }

    public function unpaidInvoicesPdf(Request $request): Response
    {
        $report = $this->build($request);

        return Pdf::loadView('reports.unpaid-invoices-pdf', [
            'invoices' => $report['unpaidInvoices'],
            'filters' => $this->filters($request),
            'company' => CompanySetting::query()->pluck('value', 'key')->all(),
        ])->setPaper('a4')->stream('factures-impayees.pdf');
    }

    public function excel(Request $request): Response
    {
        $html = view('reports.excel', [
            'report' => $this->build($request),
            'filters' => $this->filters($request),
            'canViewMargin' => $request->user()->hasPermission('products.view_margin'),
        ])->render();

        return ResponseFactory::make($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rapport-statistiques.xls"',
        ]);
    }

    private function build(Request $request): array
    {
        $filters = $this->filters($request);
        $invoiceStatuses = [
            InvoiceStatus::Validated->value,
            InvoiceStatus::Unpaid->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Paid->value,
        ];

        $sales = Invoice::query()
            ->selectRaw('DATE(issue_date) as period, COUNT(*) as count, SUM(total) as total')
            ->whereIn('status', $invoiceStatuses)
            ->when($filters['date_from'], fn ($query) => $query->whereDate('issue_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($query) => $query->whereDate('issue_date', '<=', $filters['date_to']))
            ->when($filters['client_id'], fn ($query) => $query->where('client_id', $filters['client_id']))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $unpaidInvoices = Invoice::query()
            ->with('client')
            ->whereIn('status', [InvoiceStatus::Validated->value, InvoiceStatus::Unpaid->value, InvoiceStatus::PartiallyPaid->value])
            ->where('balance_due', '>', 0)
            ->when($filters['date_from'], fn ($query) => $query->whereDate('issue_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($query) => $query->whereDate('issue_date', '<=', $filters['date_to']))
            ->when($filters['client_id'], fn ($query) => $query->where('client_id', $filters['client_id']))
            ->orderBy('due_date')
            ->get();

        $payments = Payment::query()
            ->selectRaw('DATE(payment_date) as period, COUNT(*) as count, SUM(amount) as total')
            ->where('status', PaymentStatus::Validated->value)
            ->when($filters['date_from'], fn ($query) => $query->whereDate('payment_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($query) => $query->whereDate('payment_date', '<=', $filters['date_to']))
            ->whereHas('invoice', fn ($query) => $query->when($filters['client_id'], fn ($query) => $query->where('client_id', $filters['client_id'])))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $lowStock = Product::query()
            ->with('category')
            ->lowStock()
            ->when($filters['product_category_id'], fn ($query) => $query->where('product_category_id', $filters['product_category_id']))
            ->orderBy('name')
            ->get();

        $suspense = StockSuspense::query()
            ->with(['client', 'product'])
            ->open()
            ->when($filters['client_id'], fn ($query) => $query->where('client_id', $filters['client_id']))
            ->when($filters['product_category_id'], fn ($query) => $query->whereHas('product', fn ($query) => $query->where('product_category_id', $filters['product_category_id'])))
            ->latest('delivered_at')
            ->get();

        $expensesByCategory = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total, COUNT(*) as count')
            ->where('expenses.status', ExpenseStatus::Validated->value)
            ->when($filters['date_from'], fn ($query) => $query->whereDate('expenses.expense_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($query) => $query->whereDate('expenses.expense_date', '<=', $filters['date_to']))
            ->when($filters['expense_category_id'], fn ($query) => $query->where('expenses.expense_category_id', $filters['expense_category_id']))
            ->groupBy('expense_categories.name')
            ->orderBy('expense_categories.name')
            ->get();

        $margin = collect();
        if ($request->user()->hasPermission('products.view_margin')) {
            $margin = InvoiceItem::query()
                ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->leftJoin('products', 'products.id', '=', 'invoice_items.product_id')
                ->selectRaw('invoice_items.product_name, SUM(invoice_items.line_total) as sales_total, SUM(invoice_items.quantity * COALESCE(products.purchase_price, 0)) as purchase_total')
                ->whereIn('invoices.status', $invoiceStatuses)
                ->when($filters['date_from'], fn ($query) => $query->whereDate('invoices.issue_date', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn ($query) => $query->whereDate('invoices.issue_date', '<=', $filters['date_to']))
                ->when($filters['client_id'], fn ($query) => $query->where('invoices.client_id', $filters['client_id']))
                ->when($filters['product_category_id'], fn ($query) => $query->where('products.product_category_id', $filters['product_category_id']))
                ->groupBy('invoice_items.product_name')
                ->orderByDesc(DB::raw('SUM(invoice_items.line_total)'))
                ->get()
                ->map(function ($row) {
                    $row->margin = (float) $row->sales_total - (float) $row->purchase_total;

                    return $row;
                });
        }

        return compact('sales', 'unpaidInvoices', 'payments', 'lowStock', 'suspense', 'expensesByCategory', 'margin');
    }

    private function filters(Request $request): array
    {
        return [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'client_id' => $request->integer('client_id') ?: null,
            'expense_category_id' => $request->integer('expense_category_id') ?: null,
            'product_category_id' => $request->integer('product_category_id') ?: null,
        ];
    }
}
