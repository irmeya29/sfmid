<?php

namespace App\Policies;

use App\Enums\DeliveryNoteStatus;
use App\Models\DeliveryNote;
use App\Models\User;

class DeliveryNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('delivery_notes.view');
    }

    public function view(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('delivery_notes.create');
    }

    public function update(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.update')
            && (
                $deliveryNote->status->isEditable()
                || (
                    $user->hasPermission('sensitive.update_validated_document')
                    && $deliveryNote->status !== DeliveryNoteStatus::Invoiced
                    && $deliveryNote->status !== DeliveryNoteStatus::Cancelled
                    && ! $deliveryNote->hasStockAlreadyMoved()
                )
            );
    }

    public function delete(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.delete_draft')
            && $deliveryNote->status === DeliveryNoteStatus::Draft;
    }

    public function submit(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.submit')
            && in_array($deliveryNote->status, [
                DeliveryNoteStatus::Draft,
                DeliveryNoteStatus::Rejected,
                DeliveryNoteStatus::Corrected,
            ], true)
            && $deliveryNote->items()->exists();
    }

    public function validate(User $user, DeliveryNote $deliveryNote): bool
    {
        if (! $user->hasPermission('delivery_notes.validate')) {
            return false;
        }

        if ($deliveryNote->status !== DeliveryNoteStatus::PendingValidation) {
            return false;
        }

        if (
            $deliveryNote->created_by === $user->id
            && ! $user->hasPermission('sensitive.validate_own_documents')
        ) {
            return false;
        }

        return true;
    }

    public function reject(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.reject')
            && $deliveryNote->status === DeliveryNoteStatus::PendingValidation;
    }

    public function markPrepared(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.mark_prepared')
            && $deliveryNote->status === DeliveryNoteStatus::Validated;
    }

    public function markDelivered(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.mark_delivered')
            && in_array($deliveryNote->status, [
                DeliveryNoteStatus::Validated,
                DeliveryNoteStatus::Prepared,
            ], true)
            && ! $deliveryNote->hasStockAlreadyMoved();
    }

    public function convertToInvoice(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.convert_to_invoice')
            && $deliveryNote->status === DeliveryNoteStatus::Delivered
            && $deliveryNote->invoice === null
            && $deliveryNote->hasStockAlreadyMoved();
    }

    public function cancel(User $user, DeliveryNote $deliveryNote): bool
    {
        if (! $user->hasPermission('delivery_notes.cancel')) {
            return false;
        }

        if ($deliveryNote->status === DeliveryNoteStatus::Cancelled) {
            return false;
        }

        if (
            $deliveryNote->status === DeliveryNoteStatus::Delivered
            && ! $user->hasPermission('sensitive.cancel_delivered_delivery_note')
        ) {
            return false;
        }

        return true;
    }

    public function exportPdf(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasPermission('delivery_notes.export_pdf')
            && $deliveryNote->status !== DeliveryNoteStatus::Draft;
    }
}
