<?php

namespace App\Actions\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ValidationAction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ValidatePaymentAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(Payment $payment, User $user, ?string $comment = null): Payment
    {
        if (! $user->can('validate', $payment)) {
            throw new AuthorizationException('Action non autorisee.');
        }

        return DB::transaction(function () use ($payment, $user, $comment): Payment {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== PaymentStatus::PendingValidation) {
                throw new RuntimeException('Le paiement doit etre en attente de validation.');
            }

            $invoice = Invoice::query()
                ->with(['stockSuspenses.deliveryNote'])
                ->whereKey($payment->invoice_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invoice->isPayable()) {
                throw new RuntimeException('Cette facture ne peut pas recevoir de paiement.');
            }

            $amount = (float) $payment->amount;
            $paidBefore = (float) $invoice->paid_amount;
            $balanceBefore = (float) $invoice->balance_due;

            if ($amount <= 0) {
                throw new RuntimeException('Le montant du paiement doit etre superieur a zero.');
            }

            if ($amount > $balanceBefore) {
                throw new RuntimeException('Le montant du paiement depasse le reste a payer.');
            }

            $paidAfter = $paidBefore + $amount;
            $balanceAfter = max(0, $balanceBefore - $amount);

            $invoiceStatus = $balanceAfter <= 0
                ? InvoiceStatus::Paid
                : InvoiceStatus::PartiallyPaid;

            $paymentFromStatus = $payment->status->value;
            $invoiceFromStatus = $invoice->status->value;

            $payment->forceFill([
                'status' => PaymentStatus::Validated,
                'validated_by' => $user->id,
                'validated_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $invoice->forceFill([
                'status' => $invoiceStatus,
                'paid_amount' => $paidAfter,
                'balance_due' => $balanceAfter,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $payment,
                action: ValidationAction::Validate,
                fromStatus: $paymentFromStatus,
                toStatus: PaymentStatus::Validated->value,
                comment: $comment
            );

            $this->activityLogger->log(
                action: 'validated',
                module: 'payments',
                description: "Paiement {$payment->number} valide.",
                subject: $payment,
                oldValues: ['status' => $paymentFromStatus],
                newValues: [
                    'status' => PaymentStatus::Validated->value,
                    'validated_by' => $user->id,
                ],
            );

            $this->activityLogger->log(
                action: 'payment_applied',
                module: 'invoices',
                description: "Paiement {$payment->number} applique a la facture {$invoice->number}.",
                subject: $invoice,
                oldValues: [
                    'status' => $invoiceFromStatus,
                    'paid_amount' => $paidBefore,
                    'balance_due' => $balanceBefore,
                ],
                newValues: [
                    'status' => $invoiceStatus->value,
                    'paid_amount' => $paidAfter,
                    'balance_due' => $balanceAfter,
                ],
            );

            return $payment->refresh()->load(['invoice.stockSuspenses']);
        });
    }
}
