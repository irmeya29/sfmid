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

class ValidateExpenseAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(Expense $expense, User $user, ?string $comment = null): Expense
    {
        if (! $user->can('validate', $expense)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($expense, $user, $comment): Expense {
            $expense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $expense->status->value;

            if ($expense->status !== ExpenseStatus::PendingValidation) {
                throw new AuthorizationException('La dépense n’est pas en attente de validation.');
            }

            $expense->forceFill([
                'status' => ExpenseStatus::Validated,
                'validated_by' => $user->id,
                'validated_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $expense,
                action: ValidationAction::Validate,
                fromStatus: $fromStatus,
                toStatus: ExpenseStatus::Validated->value,
                comment: $comment,
            );

            $this->activityLogger->log(
                action: 'validated',
                module: 'expenses',
                description: "Dépense {$expense->number} validée.",
                subject: $expense,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => ExpenseStatus::Validated->value, 'validated_by' => $user->id],
            );

            return $expense->refresh();
        });
    }
}
