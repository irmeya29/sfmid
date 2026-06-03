<?php

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        if (! $user->hasPermission('expenses.view')) {
            return false;
        }

        if ($expense->category?->is_sensitive) {
            return $user->hasPermission('expenses.view_sensitive')
                || $user->hasPermission('sensitive.view_salaries');
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expenses.update')
            && in_array($expense->status, [
                ExpenseStatus::Draft,
                ExpenseStatus::Rejected,
                ExpenseStatus::Corrected,
            ], true);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expenses.delete_draft')
            && $expense->status === ExpenseStatus::Draft;
    }

    public function submit(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expenses.submit')
            && in_array($expense->status, [
                ExpenseStatus::Draft,
                ExpenseStatus::Rejected,
                ExpenseStatus::Corrected,
            ], true);
    }

    public function validate(User $user, Expense $expense): bool
    {
        if (! $user->hasPermission('expenses.validate')) {
            return false;
        }

        if ($expense->status !== ExpenseStatus::PendingValidation) {
            return false;
        }

        if (
            $expense->created_by === $user->id
            && ! $user->hasPermission('sensitive.validate_own_documents')
        ) {
            return false;
        }

        return true;
    }

    public function reject(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expenses.reject')
            && $expense->status === ExpenseStatus::PendingValidation;
    }

    public function cancel(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expenses.cancel')
            && $expense->status !== ExpenseStatus::Cancelled;
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('expenses.export');
    }
}
