<?php

namespace App\Http\Controllers;

use App\Actions\Payments\SavePaymentAction;
use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['invoice.client', 'creator'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($query) => $query->where('number', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::options(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Payment::class);

        $invoices = Invoice::query()->with('client')->unpaid()->latest()->get();
        $invoice = $request->filled('invoice_id')
            ? Invoice::query()->with('client')->find($request->integer('invoice_id'))
            : null;

        return view('payments.create', [
            'invoices' => $invoices,
            'invoice' => $invoice,
            'paymentModes' => PaymentMode::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StorePaymentRequest $request, SavePaymentAction $action): RedirectResponse
    {
        Gate::authorize('create', Payment::class);

        $payment = $action->execute(
            data: $request->validated(),
            user: $request->user(),
            attachment: $request->file('attachment'),
        );

        return redirect()->route('payments.show', $payment)->with('success', 'Paiement créé avec succès.');
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $payment->load(['invoice.client', 'invoice.payments', 'creator', 'validator', 'rejector', 'validationHistories.user']);

        return view('payments.show', compact('payment'));
    }

    public function receipt(Payment $payment): Response
    {
        Gate::authorize('exportReceiptPdf', $payment);

        $payment->load(['invoice.client', 'validator']);
        $company = CompanySetting::query()->pluck('value', 'key')->all();

        return Pdf::loadView('payments.receipt', [
            'payment' => $payment,
            'company' => $company,
        ])->setPaper('a4')->stream($payment->number.'.pdf');
    }

    public function cashJournal(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $query = Payment::query()
            ->with(['invoice.client', 'validator'])
            ->validated()
            ->when($request->filled('date'), fn ($query) => $query->whereDate('payment_date', $request->date('date')))
            ->latest('payment_date');

        $total = (clone $query)->sum('amount');

        $payments = $query
            ->paginate(20)
            ->withQueryString();

        return view('payments.cash-journal', [
            'payments' => $payments,
            'date' => $request->string('date')->toString(),
            'total' => $total,
        ]);
    }
}
