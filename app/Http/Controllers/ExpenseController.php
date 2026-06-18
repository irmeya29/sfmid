<?php

namespace App\Http\Controllers;

use App\Actions\Expenses\SaveExpenseAction;
use App\Enums\ExpenseStatus;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\Audit\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Expense::class);

        $expenses = $this->baseQuery($request)
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => $this->visibleCategories($request),
            'statuses' => ExpenseStatus::options(),
            'filters' => [
                'category_id' => $request->integer('category_id') ?: null,
                'status' => $request->string('status')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Expense::class);

        return view('expenses.create', [
            'expense' => new Expense(['expense_date' => now()]),
            'categories' => $this->visibleCategories($request),
        ]);
    }

    public function store(StoreExpenseRequest $request, SaveExpenseAction $action): RedirectResponse
    {
        Gate::authorize('create', Expense::class);

        $expense = $action->execute(
            data: $request->validated(),
            user: $request->user(),
            attachment: $request->file('attachment'),
        );

        return redirect()->route('expenses.show', $expense)->with('success', 'Dépense créée avec succès.');
    }

    public function show(Expense $expense): View
    {
        Gate::authorize('view', $expense->load('category'));

        $expense->load(['creator', 'validator', 'rejector', 'validationHistories.user']);

        return view('expenses.show', compact('expense'));
    }

    public function attachment(Expense $expense): Response
    {
        Gate::authorize('view', $expense->load('category'));

        abort_if(! $expense->attachment_path, 404);

        $disk = Storage::disk('local')->exists($expense->attachment_path)
            ? Storage::disk('local')
            : (Storage::disk('public')->exists($expense->attachment_path) ? Storage::disk('public') : null);

        abort_if($disk === null, 404);

        return response()->file($disk->path($expense->attachment_path), [
            'Content-Disposition' => 'inline; filename="'.basename($expense->attachment_path).'"',
        ]);
    }

    public function edit(Request $request, Expense $expense): View
    {
        Gate::authorize('update', $expense->load('category'));

        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => $this->visibleCategories($request),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, SaveExpenseAction $action): RedirectResponse
    {
        Gate::authorize('update', $expense->load('category'));

        $expense = $action->execute(
            data: $request->validated(),
            user: $request->user(),
            attachment: $request->file('attachment'),
            expense: $expense,
        );

        return redirect()->route('expenses.show', $expense)->with('success', 'Dépense modifiée avec succès.');
    }

    public function destroy(Expense $expense, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('delete', $expense->load('category'));

        DB::transaction(function () use ($expense, $activityLogger): void {
            $oldValues = $expense->only(['number', 'status', 'amount', 'expense_category_id', 'beneficiary']);
            $attachmentPath = $expense->attachment_path;

            $activityLogger->log(
                action: 'deleted',
                module: 'expenses',
                description: "Depense {$expense->number} supprimee.",
                subject: $expense,
                oldValues: $oldValues,
            );

            $expense->delete();

            if ($attachmentPath) {
                Storage::disk('local')->delete($attachmentPath);
                Storage::disk('public')->delete($attachmentPath);
            }
        });

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Depense supprimee avec succes.');
    }

    public function pdf(Request $request): Response
    {
        Gate::authorize('export', Expense::class);

        $rows = $this->baseQuery($request)->latest('expense_date')->get();
        $company = CompanySetting::query()->pluck('value', 'key')->all();

        return Pdf::loadView('expenses.pdf', [
            'expenses' => $rows,
            'company' => $company,
            'filters' => $request->only(['category_id', 'status', 'date_from', 'date_to']),
        ])->setPaper('a4')->stream('rapport-depenses.pdf');
    }

    private function baseQuery(Request $request): Builder
    {
        return Expense::query()
            ->with(['category', 'creator'])
            ->when(! $this->canViewSensitive($request), fn (Builder $query) => $query->whereDoesntHave('category', fn (Builder $query) => $query->where('is_sensitive', true)))
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('expense_category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('expense_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('expense_date', '<=', $request->date('date_to')));
    }

    private function visibleCategories(Request $request)
    {
        return ExpenseCategory::query()
            ->active()
            ->when(! $this->canViewSensitive($request), fn (Builder $query) => $query->where('is_sensitive', false))
            ->orderBy('name')
            ->get();
    }

    private function canViewSensitive(Request $request): bool
    {
        return $request->user()?->hasPermission('expenses.view_sensitive')
            || $request->user()?->hasPermission('sensitive.view_salaries');
    }
}
