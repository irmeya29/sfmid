<?php

namespace App\Http\Controllers;

use App\Actions\Expenses\RejectExpenseAction;
use App\Actions\Expenses\SubmitExpenseAction;
use App\Actions\Expenses\ValidateExpenseAction;
use App\Http\Requests\RejectDocumentRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseValidationController extends Controller
{
    public function submit(Expense $expense, Request $request, SubmitExpenseAction $action): RedirectResponse
    {
        $action->execute($expense, $request->user());

        return redirect()->route('expenses.show', $expense)->with('success', 'Dépense soumise pour validation.');
    }

    public function validate(Expense $expense, Request $request, ValidateExpenseAction $action): RedirectResponse
    {
        $action->execute($expense, $request->user());

        return redirect()->route('expenses.show', $expense)->with('success', 'Dépense validée.');
    }

    public function reject(Expense $expense, RejectDocumentRequest $request, RejectExpenseAction $action): RedirectResponse
    {
        $action->execute($expense, $request->user(), $request->validated('reason'));

        return redirect()->route('expenses.show', $expense)->with('success', 'Dépense rejetée avec motif.');
    }
}
