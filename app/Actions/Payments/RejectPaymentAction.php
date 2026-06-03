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
use InvalidArgumentException;

class RejectPaymentAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(Payment $payment, User $user, string $reason): Payment
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Le motif de rejet est obligatoire.');
        }

        if (! $user->can('reject', $payment)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($payment, $user, $reason): Payment {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $payment->status->value;

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $payment,
                action: ValidationAction::Reject,
                fromStatus: $fromStatus,
                toStatus: PaymentStatus::Rejected->value,
                reason: $reason,
            );

            $this->activityLogger->log(
                action: 'rejected',
                module: 'payments',
                description: "Paiement {$payment->number} rejeté.",
                subject: $payment,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => PaymentStatus::Rejected->value, 'rejection_reason' => $reason],
            );

            return $payment->refresh();
        });
    }
}
