<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Enums\ValidationAction;
use App\Models\Payment;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmitPaymentAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(Payment $payment, User $user): Payment
    {
        if (! $user->can('submit', $payment)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($payment): Payment {
            $payment = Payment::query()
                ->with('invoice')
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($payment->status, [
                PaymentStatus::Draft,
                PaymentStatus::Rejected,
            ], true)) {
                throw new RuntimeException('Seul un paiement brouillon ou rejeté peut être soumis.');
            }

            if ((float) $payment->amount <= 0) {
                throw new RuntimeException('Le montant du paiement doit être supérieur à zéro.');
            }

            if ((float) $payment->amount > (float) $payment->invoice->balance_due) {
                throw new RuntimeException('Le montant du paiement dépasse le reste à payer.');
            }

            $fromStatus = $payment->status->value;

            $payment->forceFill([
                'status' => PaymentStatus::PendingValidation,
                'submitted_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $payment,
                action: ValidationAction::Submit,
                fromStatus: $fromStatus,
                toStatus: PaymentStatus::PendingValidation->value,
                comment: 'Paiement soumis pour validation.'
            );

            $this->activityLogger->log(
                action: 'submitted',
                module: 'payments',
                description: "Paiement {$payment->number} soumis pour validation.",
                subject: $payment,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => PaymentStatus::PendingValidation->value],
            );

            return $payment->refresh();
        });
    }
}
