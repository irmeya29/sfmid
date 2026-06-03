<?php

namespace App\Actions\Validation;

use App\Enums\InvoiceStatus;
use App\Enums\ValidationAction;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SubmitInvoiceAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(Invoice $invoice, User $user): Invoice
    {
        if (! $user->can('submit', $invoice)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($invoice): Invoice {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $invoice->status->value;

            $invoice->forceFill([
                'status' => InvoiceStatus::PendingValidation,
                'submitted_at' => now(),
                'rejection_reason' => null,
                'rejected_by' => null,
                'rejected_at' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $invoice,
                action: ValidationAction::Submit,
                fromStatus: $fromStatus,
                toStatus: InvoiceStatus::PendingValidation->value,
                comment: 'Facture soumise pour validation.'
            );

            $this->activityLogger->log(
                action: 'submitted',
                module: 'invoices',
                description: "Facture {$invoice->number} soumise pour validation.",
                subject: $invoice,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => InvoiceStatus::PendingValidation->value],
            );

            return $invoice->refresh();
        });
    }
}
