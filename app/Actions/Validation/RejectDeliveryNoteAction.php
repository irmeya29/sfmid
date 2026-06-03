<?php

namespace App\Actions\Validation;

use App\Enums\DeliveryNoteStatus;
use App\Enums\ValidationAction;
use App\Models\DeliveryNote;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RejectDeliveryNoteAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(DeliveryNote $deliveryNote, User $user, string $reason): DeliveryNote
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Le motif de rejet est obligatoire.');
        }

        if (! $user->can('reject', $deliveryNote)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($deliveryNote, $user, $reason): DeliveryNote {
            $deliveryNote = DeliveryNote::query()->whereKey($deliveryNote->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $deliveryNote->status->value;

            if ($deliveryNote->status !== DeliveryNoteStatus::PendingValidation) {
                throw new AuthorizationException('Le BL n’est pas en attente de validation.');
            }

            $deliveryNote->forceFill([
                'status' => DeliveryNoteStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $deliveryNote,
                action: ValidationAction::Reject,
                fromStatus: $fromStatus,
                toStatus: DeliveryNoteStatus::Rejected->value,
                reason: $reason,
            );

            $this->activityLogger->log(
                action: 'rejected',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} rejeté.",
                subject: $deliveryNote,
                oldValues: ['status' => $fromStatus],
                newValues: [
                    'status' => DeliveryNoteStatus::Rejected->value,
                    'rejected_by' => $user->id,
                    'rejection_reason' => $reason,
                ],
            );

            return $deliveryNote->refresh();
        });
    }
}
