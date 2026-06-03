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
use InvalidArgumentException;

class RejectExpenseAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(Expense $expense, User $user, string $reason): Expense
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Le motif de rejet est obligatoire.');
        }

        if (! $user->can('reject', $expense)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($expense, $user, $reason): Expense {
            $expense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $expense->status->value;

            if ($expense->status !== ExpenseStatus::PendingValidation) {
                throw new AuthorizationException('La dépense n’est pas en attente de validation.');
            }

            $expense->forceFill([
                'status' => ExpenseStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $expense,
                action: ValidationAction::Reject,
                fromStatus: $fromStatus,
                toStatus: ExpenseStatus::Rejected->value,
                reason: $reason,
            );

            $this->activityLogger->log(
                action: 'rejected',
                module: 'expenses',
                description: "Dépense {$expense->number} rejetée.",
                subject: $expense,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => ExpenseStatus::Rejected->value, 'rejection_reason' => $reason],
            );

            return $expense->refresh();
        });
    }
}
