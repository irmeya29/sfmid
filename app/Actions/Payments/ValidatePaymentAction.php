<?php

namespace App\Actions\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementDirection;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Enums\ValidationAction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
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
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($payment, $user, $comment): Payment {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== PaymentStatus::PendingValidation) {
                throw new RuntimeException('Le paiement doit être en attente de validation.');
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
                throw new RuntimeException('Le montant du paiement doit être supérieur à zéro.');
            }

            if ($amount > $balanceBefore) {
                throw new RuntimeException('Le montant du paiement dépasse le reste à payer.');
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
                description: "Paiement {$payment->number} validé.",
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
                description: "Paiement {$payment->number} appliqué à la facture {$invoice->number}.",
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

            if ($invoiceStatus === InvoiceStatus::Paid) {
                $this->closeSuspenseStock($invoice, $user);
            }

            return $payment->refresh()->load(['invoice.stockSuspenses']);
        });
    }

    private function closeSuspenseStock(Invoice $invoice, User $user): void
    {
        foreach ($invoice->stockSuspenses()->where('status', 'open')->get() as $suspense) {
            $product = Product::query()
                ->whereKey($suspense->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantityToClose = (float) $suspense->quantity - (float) $suspense->closed_quantity;

            if ($quantityToClose <= 0) {
                continue;
            }

            $physicalBefore = (float) $product->physical_stock;
            $reservedBefore = (float) $product->reserved_stock;
            $suspenseBefore = (float) $product->suspense_stock;
            $toolBefore = (float) $product->tool_stock;

            if ($suspenseBefore < $quantityToClose) {
                throw new RuntimeException("Stock en suspens incohérent pour {$product->code} - {$product->name}.");
            }

            $suspenseAfter = $suspenseBefore - $quantityToClose;

            $product->forceFill([
                'suspense_stock' => $suspenseAfter,
            ])->save();

            $suspense->forceFill([
                'closed_quantity' => $suspense->quantity,
                'status' => 'closed',
                'closed_at' => now(),
                'closing_reason' => "Facture {$invoice->number} entièrement payée.",
                'closed_by' => $user->id,
            ])->save();

            $suspense->deliveryNote->stockMovements()->create([
                'product_id' => $product->id,
                'type' => StockMovementType::SuspenseClose,
                'direction' => StockMovementDirection::CloseSuspense,
                'status' => StockMovementStatus::Validated,
                'quantity' => $quantityToClose,
                'unit_cost' => $product->purchase_price,
                'physical_before' => $physicalBefore,
                'physical_after' => $physicalBefore,
                'reserved_before' => $reservedBefore,
                'reserved_after' => $reservedBefore,
                'suspense_before' => $suspenseBefore,
                'suspense_after' => $suspenseAfter,
                'tool_before' => $toolBefore,
                'tool_after' => $toolBefore,
                'reason' => "Clôture stock en suspens après paiement complet facture {$invoice->number}.",
                'created_by' => $user->id,
                'validated_by' => $user->id,
                'validated_at' => now(),
            ]);

            $this->activityLogger->log(
                action: 'closed_suspense_stock',
                module: 'stock',
                description: "Stock en suspens clôturé pour la facture {$invoice->number}.",
                subject: $suspense,
                oldValues: [
                    'status' => 'open',
                    'closed_quantity' => (float) $suspense->getOriginal('closed_quantity'),
                    'product_suspense_stock' => $suspenseBefore,
                ],
                newValues: [
                    'status' => 'closed',
                    'closed_quantity' => (float) $suspense->quantity,
                    'product_suspense_stock' => $suspenseAfter,
                ],
            );
        }
    }
}
