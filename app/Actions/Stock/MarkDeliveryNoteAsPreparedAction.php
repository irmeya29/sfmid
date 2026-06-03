<?php

namespace App\Actions\Stock;

use App\Enums\DeliveryNoteStatus;
use App\Models\DeliveryNote;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class MarkDeliveryNoteAsPreparedAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(DeliveryNote $deliveryNote, User $user): DeliveryNote
    {
        if (! $user->can('markPrepared', $deliveryNote)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($deliveryNote): DeliveryNote {
            $deliveryNote = DeliveryNote::query()->whereKey($deliveryNote->id)->lockForUpdate()->firstOrFail();

            if ($deliveryNote->status !== DeliveryNoteStatus::Validated) {
                throw new AuthorizationException('Le BL doit être validé avant préparation.');
            }

            $deliveryNote->forceFill([
                'status' => DeliveryNoteStatus::Prepared,
            ])->save();

            $this->activityLogger->log(
                action: 'marked_prepared',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} marqué comme préparé.",
                subject: $deliveryNote,
                oldValues: ['status' => DeliveryNoteStatus::Validated->value],
                newValues: ['status' => DeliveryNoteStatus::Prepared->value],
            );

            return $deliveryNote->refresh();
        });
    }
}
