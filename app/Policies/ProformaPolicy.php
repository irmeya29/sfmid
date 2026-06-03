<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\Proforma;
use App\Models\User;

class ProformaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('proformas.view');
    }

    public function view(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('proformas.create');
    }

    public function update(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.update')
            && $proforma->status->isEditable();
    }

    public function delete(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.delete_draft')
            && $proforma->status === DocumentStatus::Draft;
    }

    public function submit(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.submit')
            && $proforma->status->canBeSubmitted()
            && $proforma->items()->exists();
    }

    public function validate(User $user, Proforma $proforma): bool
    {
        if (! $user->hasPermission('proformas.validate')) {
            return false;
        }

        if ($proforma->status !== DocumentStatus::PendingValidation) {
            return false;
        }

        if (
            $proforma->created_by === $user->id
            && ! $user->hasPermission('sensitive.validate_own_documents')
        ) {
            return false;
        }

        return true;
    }

    public function reject(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.reject')
            && $proforma->status === DocumentStatus::PendingValidation;
    }

    public function cancel(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.cancel')
            && $proforma->status !== DocumentStatus::Cancelled
            && $proforma->status !== DocumentStatus::Converted;
    }

    public function convertToDeliveryNote(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.convert_to_delivery_note')
            && $proforma->status === DocumentStatus::Validated
            && $proforma->deliveryNote === null;
    }

    public function exportPdf(User $user, Proforma $proforma): bool
    {
        return $user->hasPermission('proformas.export_pdf')
            && $proforma->status !== DocumentStatus::Draft;
    }
}
