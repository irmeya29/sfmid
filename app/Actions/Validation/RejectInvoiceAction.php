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
use InvalidArgumentException;

class RejectInvoiceAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(Invoice $invoice, User $user, string $reason): Invoice
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Le motif de rejet est obligatoire.');
        }

        if (! $user->can('reject', $invoice)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($invoice, $user, $reason): Invoice {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $invoice->status->value;

            if ($invoice->status !== InvoiceStatus::PendingValidation) {
                throw new AuthorizationException('La facture n’est pas en attente de validation.');
            }

            $invoice->forceFill([
                'status' => InvoiceStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $invoice,
                action: ValidationAction::Reject,
                fromStatus: $fromStatus,
                toStatus: InvoiceStatus::Rejected->value,
                reason: $reason,
            );

            $this->activityLogger->log(
                action: 'rejected',
                module: 'invoices',
                description: "Facture {$invoice->number} rejetée.",
                subject: $invoice,
                oldValues: ['status' => $fromStatus],
                newValues: [
                    'status' => InvoiceStatus::Rejected->value,
                    'rejection_reason' => $reason,
                ],
            );

            return $invoice->refresh();
        });
    }
}
