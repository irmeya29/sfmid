<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementStatus;
use App\Models\Client;
use App\Models\DeliveryNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\StockMovement;
use App\Models\StockSuspense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const START_YEAR = 2026;

    public function index(Request $request): View
    {
        [$dateFrom, $dateTo, $periodLabel] = $this->resolvePeriod($request);

        $validatedInvoiceStatuses = [
            InvoiceStatus::Validated->value,
            InvoiceStatus::Unpaid->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Paid->value,
        ];

        $openInvoiceStatuses = [
            InvoiceStatus::Validated->value,
            InvoiceStatus::Unpaid->value,
            InvoiceStatus::PartiallyPaid->value,
        ];

        $periodInvoices = Invoice::query()
            ->whereIn('status', $validatedInvoiceStatuses)
            ->whereBetween('issue_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $periodReceipts = Payment::query()
            ->where('status', PaymentStatus::Validated->value)
            ->whereBetween('payment_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $periodExpenses = Expense::query()
            ->where('status', ExpenseStatus::Validated->value)
            ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $stats = [
            'period_sales_amount' => (clone $periodInvoices)->sum('total'),
            'period_receipts_amount' => (clone $periodReceipts)->sum('amount'),
            'period_expenses_amount' => (clone $periodExpenses)->sum('amount'),
            'period_invoice_count' => (clone $periodInvoices)->count(),

            'unpaid_invoices_count' => Invoice::query()
                ->whereIn('status', $openInvoiceStatuses)
                ->where('balance_due', '>', 0)
                ->count(),

            'unpaid_invoices_amount' => Invoice::query()
                ->whereIn('status', $openInvoiceStatuses)
                ->sum('balance_due'),

            'pending_validations_count' =>
                Proforma::query()->where('status', DocumentStatus::PendingValidation->value)->count()
                + DeliveryNote::query()->where('status', DocumentStatus::PendingValidation->value)->count()
                + Invoice::query()->where('status', InvoiceStatus::PendingValidation->value)->count()
                + Payment::query()->where('status', PaymentStatus::PendingValidation->value)->count()
                + Expense::query()->where('status', ExpenseStatus::PendingValidation->value)->count()
                + StockMovement::query()->where('status', StockMovementStatus::PendingValidation->value)->count(),

            'low_stock_count' => Product::query()
                ->whereColumn('physical_stock', '<=', 'alert_threshold')
                ->count(),

            'open_suspenses_count' => StockSuspense::query()
                ->where('status', 'open')
                ->count(),

            'overdue_invoices_count' => Invoice::query()
                ->unpaid()
                ->whereDate('due_date', '<', today())
                ->count(),

            'clients_count' => Client::query()->count(),
            'products_count' => Product::query()->count(),
        ];

        $stats['period_balance'] = (float) $stats['period_receipts_amount'] - (float) $stats['period_expenses_amount'];

        $alerts = [
            [
                'label' => 'Validations en attente',
                'value' => $stats['pending_validations_count'],
                'tone' => $stats['pending_validations_count'] > 0 ? 'yellow' : 'green',
                'route' => 'validations.index',
                'permission' => 'validations.view',
            ],
            [
                'label' => 'Factures échues',
                'value' => $stats['overdue_invoices_count'],
                'tone' => $stats['overdue_invoices_count'] > 0 ? 'red' : 'green',
                'route' => 'invoices.index',
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Stock bas',
                'value' => $stats['low_stock_count'],
                'tone' => $stats['low_stock_count'] > 0 ? 'red' : 'green',
                'route' => 'stock.reports.low-stock',
                'permission' => 'stock.view',
            ],
            [
                'label' => 'Stock en suspens',
                'value' => $stats['open_suspenses_count'],
                'tone' => $stats['open_suspenses_count'] > 0 ? 'purple' : 'green',
                'route' => 'stock.suspense',
                'permission' => 'stock.view',
            ],
        ];

        $latestInvoices = Invoice::query()
            ->with('client')
            ->whereIn('status', $openInvoiceStatuses)
            ->where('balance_due', '>', 0)
            ->latest('due_date')
            ->limit(5)
            ->get();

        $lowStockProducts = Product::query()
            ->whereColumn('physical_stock', '<=', 'alert_threshold')
            ->orderBy('physical_stock')
            ->limit(5)
            ->get();

        $filters = [
            'period' => $request->string('period', 'month')->toString(),
            'month' => (int) $request->integer('month', now()->month),
            'year' => (int) $request->integer('year', now()->year),
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'period_label' => $periodLabel,
            'start_year' => self::START_YEAR,
        ];

        return view('dashboard.index', compact('stats', 'alerts', 'latestInvoices', 'lowStockProducts', 'filters'));
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->string('period', 'month')->toString();
        $year = max(self::START_YEAR, min(2100, (int) $request->integer('year', now()->year)));
        $month = max(1, min(12, (int) $request->integer('month', now()->month)));

        if ($period === 'year') {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 12, 31)->endOfDay();

            return [$start, $end, (string) $year];
        }

        if ($period === 'custom') {
            $minimumStart = Carbon::create(self::START_YEAR, 1, 1)->startOfDay();
            $start = Carbon::parse($request->input('date_from', now()->startOfMonth()->toDateString()))->startOfDay()->max($minimumStart);
            $end = Carbon::parse($request->input('date_to', now()->endOfMonth()->toDateString()))->endOfDay()->max($minimumStart);

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [$start, $end, $start->format('d/m/Y').' - '.$end->format('d/m/Y')];
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return [$start, $end, $start->translatedFormat('F Y')];
    }
}
