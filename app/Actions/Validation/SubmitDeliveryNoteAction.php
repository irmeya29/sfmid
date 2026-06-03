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

class SubmitDeliveryNoteAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(DeliveryNote $deliveryNote, User $user): DeliveryNote
    {
        if (! $user->can('submit', $deliveryNote)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($deliveryNote): DeliveryNote {
            $deliveryNote = DeliveryNote::query()->whereKey($deliveryNote->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $deliveryNote->status->value;

            $deliveryNote->forceFill([
                'status' => DeliveryNoteStatus::PendingValidation,
                'submitted_at' => now(),
                'rejection_reason' => null,
                'rejected_by' => null,
                'rejected_at' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $deliveryNote,
                action: ValidationAction::Submit,
                fromStatus: $fromStatus,
                toStatus: DeliveryNoteStatus::PendingValidation->value,
                comment: 'BL soumis pour validation.'
            );

            $this->activityLogger->log(
                action: 'submitted',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} soumis pour validation.",
                subject: $deliveryNote,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => DeliveryNoteStatus::PendingValidation->value],
            );

            return $deliveryNote->refresh();
        });
    }
}
