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

class ValidateInvoiceAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(Invoice $invoice, User $user, ?string $comment = null): Invoice
    {
        if (! $user->can('validate', $invoice)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($invoice, $user, $comment): Invoice {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $invoice->status->value;

            if ($invoice->status !== InvoiceStatus::PendingValidation) {
                throw new AuthorizationException('La facture n’est pas en attente de validation.');
            }

            $invoice->forceFill([
                'status' => ((float) $invoice->balance_due > 0) ? InvoiceStatus::Unpaid : InvoiceStatus::Paid,
                'validated_by' => $user->id,
                'validated_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $invoice,
                action: ValidationAction::Validate,
                fromStatus: $fromStatus,
                toStatus: $invoice->status->value,
                comment: $comment
            );

            $this->activityLogger->log(
                action: 'validated',
                module: 'invoices',
                description: "Facture {$invoice->number} validée. Aucun stock redéduit.",
                subject: $invoice,
                oldValues: ['status' => $fromStatus],
                newValues: [
                    'status' => $invoice->status->value,
                    'validated_by' => $user->id,
                ],
            );

            return $invoice->refresh();
        });
    }
}
