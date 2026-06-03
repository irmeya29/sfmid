<?php

namespace App\Actions\Validation;

use App\Enums\DocumentStatus;
use App\Enums\ValidationAction;
use App\Models\Proforma;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SubmitProformaAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(Proforma $proforma, User $user): Proforma
    {
        if (! $user->can('submit', $proforma)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($proforma): Proforma {
            $proforma = Proforma::query()
                ->whereKey($proforma->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $proforma->status->value;

            $proforma->forceFill([
                'status' => DocumentStatus::PendingValidation,
                'submitted_at' => now(),
                'rejection_reason' => null,
                'rejected_by' => null,
                'rejected_at' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $proforma,
                action: ValidationAction::Submit,
                fromStatus: $fromStatus,
                toStatus: DocumentStatus::PendingValidation->value,
                comment: 'Proforma soumise pour validation.'
            );

            $this->activityLogger->log(
                action: 'submitted',
                module: 'proformas',
                description: "Proforma {$proforma->number} soumise pour validation.",
                subject: $proforma,
                oldValues: ['status' => $fromStatus],
                newValues: ['status' => DocumentStatus::PendingValidation->value],
            );

            return $proforma->refresh();
        });
    }
}
