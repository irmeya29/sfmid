<?php

namespace App\Policies;

use App\Enums\StockMovementStatus;
use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('stock.view');
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->hasPermission('stock.view');
    }

    public function createEntry(User $user): bool
    {
        return $user->hasPermission('stock.create_entry');
    }

    public function createExit(User $user): bool
    {
        return $user->hasPermission('stock.create_exit');
    }

    public function adjust(User $user): bool
    {
        return $user->hasPermission('stock.adjust');
    }

    public function validate(User $user, StockMovement $stockMovement): bool
    {
        if (! $user->hasPermission('stock.validate_movement')) {
            return false;
        }

        if ($stockMovement->status !== StockMovementStatus::PendingValidation) {
            return false;
        }

        if (
            $stockMovement->created_by === $user->id
            && ! $user->hasPermission('sensitive.validate_own_documents')
        ) {
            return false;
        }

        return true;
    }

    public function cancel(User $user, StockMovement $stockMovement): bool
    {
        return $user->hasPermission('stock.cancel_movement')
            && $stockMovement->status !== StockMovementStatus::Cancelled;
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('stock.export');
    }
}
