<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payments.create');
    }

    public function submit(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.submit')
            && in_array($payment->status, [
                PaymentStatus::Draft,
                PaymentStatus::Rejected,
            ], true);
    }

    public function validate(User $user, Payment $payment): bool
    {
        if (! $user->hasPermission('payments.validate')) {
            return false;
        }

        if ($payment->status !== PaymentStatus::PendingValidation) {
            return false;
        }

        if (
            $payment->created_by === $user->id
            && ! $user->hasPermission('sensitive.validate_own_documents')
        ) {
            return false;
        }

        return true;
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.reject')
            && $payment->status === PaymentStatus::PendingValidation;
    }

    public function cancel(User $user, Payment $payment): bool
    {
        if (! $user->hasPermission('payments.cancel')) {
            return false;
        }

        if ($payment->status === PaymentStatus::Cancelled) {
            return false;
        }

        if (
            $payment->status === PaymentStatus::Validated
            && ! $user->hasPermission('sensitive.cancel_validated_payment')
        ) {
            return false;
        }

        return true;
    }

    public function exportReceiptPdf(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.export_receipt_pdf')
            && $payment->status === PaymentStatus::Validated;
    }
}
