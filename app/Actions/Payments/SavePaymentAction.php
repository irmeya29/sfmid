<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SavePaymentAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user, ?UploadedFile $attachment = null): Payment
    {
        return DB::transaction(function () use ($data, $user, $attachment): Payment {
            $invoice = Invoice::query()->whereKey($data['invoice_id'])->lockForUpdate()->firstOrFail();

            if (! $invoice->isPayable()) {
                throw new RuntimeException('Cette facture ne peut pas recevoir de paiement.');
            }

            $amount = (float) $data['amount'];

            if ($amount <= 0 || $amount > (float) $invoice->balance_due) {
                throw new RuntimeException('Le montant du paiement dépasse le solde de la facture.');
            }

            $attachmentPath = $attachment?->store('payment-attachments', 'local');

            $payment = Payment::query()->create([
                'number' => $this->documentNumberGenerator->generate('payment'),
                'invoice_id' => $invoice->id,
                'status' => PaymentStatus::Draft,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'attachment_path' => $attachmentPath,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->activityLogger->log(
                action: 'created',
                module: 'payments',
                description: "Paiement {$payment->number} créé pour la facture {$invoice->number}.",
                subject: $payment,
                newValues: $payment->only(['invoice_id', 'status', 'amount', 'method', 'reference']),
            );

            return $payment->refresh()->load('invoice.client');
        });
    }
}
