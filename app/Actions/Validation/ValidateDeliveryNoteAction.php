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

class ValidateDeliveryNoteAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(DeliveryNote $deliveryNote, User $user, ?string $comment = null): DeliveryNote
    {
        if (! $user->can('validate', $deliveryNote)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($deliveryNote, $user, $comment): DeliveryNote {
            $deliveryNote = DeliveryNote::query()->whereKey($deliveryNote->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $deliveryNote->status->value;

            if ($deliveryNote->status !== DeliveryNoteStatus::PendingValidation) {
                throw new AuthorizationException('Le BL n’est pas en attente de validation.');
            }

            $deliveryNote->forceFill([
                'status' => DeliveryNoteStatus::Validated,
                'validated_by' => $user->id,
                'validated_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $deliveryNote,
                action: ValidationAction::Validate,
                fromStatus: $fromStatus,
                toStatus: DeliveryNoteStatus::Validated->value,
                comment: $comment
            );

            $this->activityLogger->log(
                action: 'validated',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} validé.",
                subject: $deliveryNote,
                oldValues: ['status' => $fromStatus],
                newValues: [
                    'status' => DeliveryNoteStatus::Validated->value,
                    'validated_by' => $user->id,
                ],
            );

            return $deliveryNote->refresh();
        });
    }
}
