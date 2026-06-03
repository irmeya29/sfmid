<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseStatus;
use App\Enums\ValidationAction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaveExpenseAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user, ?UploadedFile $attachment = null, ?Expense $expense = null): Expense
    {
        return DB::transaction(function () use ($data, $user, $attachment, $expense): Expense {
            $isUpdate = $expense instanceof Expense;

            $category = ExpenseCategory::query()->findOrFail($data['expense_category_id']);

            if ($category->is_sensitive && ! $this->canUseSensitiveCategory($user)) {
                throw new RuntimeException('Vous n’avez pas accès aux charges sensibles.');
            }

            if ($isUpdate) {
                $expense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

                if (! in_array($expense->status, [ExpenseStatus::Draft, ExpenseStatus::Rejected, ExpenseStatus::Corrected], true)) {
                    throw new RuntimeException('Cette dépense ne peut plus être modifiée.');
                }

                $oldValues = $expense->only([
                    'expense_category_id',
                    'status',
                    'amount',
                    'expense_date',
                    'beneficiary',
                    'payment_method',
                    'payment_reference',
                    'attachment_path',
                    'description',
                ]);
                $fromStatus = $expense->status;
            } else {
                $oldValues = null;
                $fromStatus = null;
                $expense = new Expense([
                    'number' => $this->documentNumberGenerator->generate('expense'),
                    'status' => ExpenseStatus::Draft,
                    'created_by' => $user->id,
                ]);
            }

            $attachmentPath = $attachment?->store('expense-attachments', 'public') ?? $expense->attachment_path;
            $newStatus = $isUpdate && $fromStatus === ExpenseStatus::Rejected
                ? ExpenseStatus::Corrected
                : $expense->status;

            $expense->fill([
                'expense_category_id' => $category->id,
                'status' => $newStatus,
                'amount' => $data['amount'],
                'expense_date' => $data['expense_date'],
                'beneficiary' => $data['beneficiary'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'attachment_path' => $attachmentPath,
                'description' => $data['description'],
            ])->save();

            if ($isUpdate && $fromStatus === ExpenseStatus::Rejected) {
                $this->validationHistoryLogger->log(
                    document: $expense,
                    action: ValidationAction::Correct,
                    fromStatus: ExpenseStatus::Rejected->value,
                    toStatus: ExpenseStatus::Corrected->value,
                    comment: 'Dépense corrigée après rejet.'
                );
            }

            $this->activityLogger->log(
                action: $isUpdate ? 'updated' : 'created',
                module: 'expenses',
                description: $isUpdate ? "Dépense {$expense->number} modifiée." : "Dépense {$expense->number} créée.",
                subject: $expense,
                oldValues: $oldValues,
                newValues: $expense->fresh()->only([
                    'expense_category_id',
                    'status',
                    'amount',
                    'expense_date',
                    'beneficiary',
                    'payment_method',
                    'payment_reference',
                    'attachment_path',
                    'description',
                ]),
            );

            return $expense->refresh()->load(['category', 'creator']);
        });
    }

    private function canUseSensitiveCategory(User $user): bool
    {
        return $user->hasPermission('expenses.view_sensitive')
            || $user->hasPermission('sensitive.view_salaries');
    }
}
