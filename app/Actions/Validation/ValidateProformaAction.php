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

class ValidateProformaAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(Proforma $proforma, User $user, ?string $comment = null): Proforma
    {
        if (! $user->can('validate', $proforma)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($proforma, $comment, $user): Proforma {
            $proforma = Proforma::query()
                ->whereKey($proforma->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $proforma->status->value;

            if ($proforma->status !== DocumentStatus::PendingValidation) {
                throw new AuthorizationException('La proforma n’est pas en attente de validation.');
            }

            $proforma->forceFill([
                'status' => DocumentStatus::Validated,
                'validated_by' => $user->id,
                'validated_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $proforma,
                action: ValidationAction::Validate,
                fromStatus: $fromStatus,
                toStatus: DocumentStatus::Validated->value,
                comment: $comment
            );

            $this->activityLogger->log(
                action: 'validated',
                module: 'proformas',
                description: "Proforma {$proforma->number} validée.",
                subject: $proforma,
                oldValues: ['status' => $fromStatus],
                newValues: [
                    'status' => DocumentStatus::Validated->value,
                    'validated_by' => $user->id,
                ],
            );

            return $proforma->refresh();
        });
    }
}
