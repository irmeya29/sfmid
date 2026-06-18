<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\StoreTreasuryExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TreasuryController extends Controller
{
    public function index(Request $request): View
    {
        $receipts = Payment::query()
            ->with(['invoice.client', 'creator'])
            ->where('status', PaymentStatus::Validated->value)
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('payment_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('payment_date', '<=', $request->date('date_to')))
            ->get()
            ->toBase()
            ->map(fn (Payment $payment): array => [
                'date' => $payment->payment_date,
                'type' => 'recette',
                'label' => 'Paiement facture '.$payment->invoice?->number,
                'category' => 'Facture client',
                'method' => $payment->method,
                'third_party' => $payment->invoice?->client?->name,
                'amount_in' => (float) $payment->amount,
                'amount_out' => 0,
            ]);

        $expenses = Expense::query()
            ->with('category')
            ->where('status', ExpenseStatus::Validated->value)
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->date('date_to')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('expense_category_id', $request->integer('category_id')))
            ->get()
            ->toBase()
            ->map(fn (Expense $expense): array => [
                'date' => $expense->expense_date,
                'type' => 'depense',
                'label' => $expense->description,
                'category' => $expense->category?->name,
                'method' => $expense->payment_method,
                'third_party' => $expense->beneficiary,
                'amount_in' => 0,
                'amount_out' => (float) $expense->amount,
            ]);

        $entries = collect($receipts->all())
            ->merge($expenses->all())
            ->sortByDesc('date')
            ->values();

        return view('treasury.index', [
            'entries' => $entries,
            'totalIn' => $entries->sum('amount_in'),
            'totalOut' => $entries->sum('amount_out'),
            'categories' => ExpenseCategory::query()->active()->orderBy('name')->get(),
            'paymentModes' => PaymentMode::query()->active()->orderBy('name')->get(),
            'showExpenseForm' => $request->string('action')->toString() === 'create_expense' || $request->session()->hasOldInput(),
            'filters' => [
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'category_id' => $request->integer('category_id') ?: null,
            ],
        ]);
    }

    public function storeExpense(StoreTreasuryExpenseRequest $request, DocumentNumberGenerator $numbers, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $attachmentPath = $request->file('attachment')?->store('expense-attachments', 'local');

        $expense = Expense::query()->create([
            'number' => $numbers->generate('expense'),
            'expense_category_id' => $data['expense_category_id'],
            'status' => ExpenseStatus::Validated,
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'],
            'beneficiary' => $data['beneficiary'] ?? null,
            'description' => $data['description'],
            'attachment_path' => $attachmentPath,
            'created_by' => $request->user()->id,
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
        ]);

        $logger->log('created', 'treasury', "Dépense courante {$expense->number} saisie en trésorerie.", $expense, newValues: $expense->toArray());

        return redirect()->route('treasury.index')->with('success', 'Dépense courante enregistrée en trésorerie.');
    }
}
