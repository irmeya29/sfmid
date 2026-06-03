<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.update')
            && in_array($invoice->status, [
                InvoiceStatus::Draft,
                InvoiceStatus::Rejected,
                InvoiceStatus::Corrected,
            ], true);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.delete_draft')
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function submit(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.submit')
            && in_array($invoice->status, [
                InvoiceStatus::Draft,
                InvoiceStatus::Rejected,
                InvoiceStatus::Corrected,
            ], true)
            && $invoice->items()->exists();
    }

    public function validate(User $user, Invoice $invoice): bool
    {
        if (! $user->hasPermission('invoices.validate')) {
            return false;
        }

        if ($invoice->status !== InvoiceStatus::PendingValidation) {
            return false;
        }

        if (
            $invoice->created_by === $user->id
            && ! $user->hasPermission('sensitive.validate_own_documents')
        ) {
            return false;
        }

        return true;
    }

    public function reject(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.reject')
            && $invoice->status === InvoiceStatus::PendingValidation;
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        if (! $user->hasPermission('invoices.cancel')) {
            return false;
        }

        if ($invoice->status === InvoiceStatus::Cancelled) {
            return false;
        }

        if (
            in_array($invoice->status, [
                InvoiceStatus::Validated,
                InvoiceStatus::Unpaid,
                InvoiceStatus::PartiallyPaid,
                InvoiceStatus::Paid,
            ], true)
            && ! $user->hasPermission('sensitive.cancel_validated_invoice')
        ) {
            return false;
        }

        return true;
    }

    public function exportPdf(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.export_pdf')
            && $invoice->status !== InvoiceStatus::Draft;
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('payments.create')
            && $invoice->isPayable();
    }
}
