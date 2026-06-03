<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseStatus;
use App\Enums\ValidationAction;
use App\Models\Expense;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SubmitExpenseAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(Expense $expense, User $user): Expense
    {
        if (! $user->can('submit', $expense)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($expense): Expense {
            $expense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $expense->status->value;

            $expense->forceFill([
                'status' => ExpenseStatus::PendingValidation,
                'submitted_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $expense,
                action: ValidationAction::Submit,
                fromStatus: $fromStatus,
                toStatus: ExpenseStatus::PendingValidation->value,
                comment: 'Dépense soumise pour validation.'
            );

            $this->activityLogger->log(
                action: 'submitted',
                module: 'expenses',
                description: "Dépense {$expense->number} soumise pour validation.",
                subject: $expense,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => ExpenseStatus::PendingValidation->value],
            );

            return $expense->refresh();
        });
    }
}
