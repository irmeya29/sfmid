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
use InvalidArgumentException;

class RejectProformaAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(Proforma $proforma, User $user, string $reason): Proforma
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Le motif de rejet est obligatoire.');
        }

        if (! $user->can('reject', $proforma)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($proforma, $user, $reason): Proforma {
            $proforma = Proforma::query()
                ->whereKey($proforma->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $proforma->status->value;

            if ($proforma->status !== DocumentStatus::PendingValidation) {
                throw new AuthorizationException('La proforma n’est pas en attente de validation.');
            }

            $proforma->forceFill([
                'status' => DocumentStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->validationHistoryLogger->log(
                document: $proforma,
                action: ValidationAction::Reject,
                fromStatus: $fromStatus,
                toStatus: DocumentStatus::Rejected->value,
                reason: $reason,
            );

            $this->activityLogger->log(
                action: 'rejected',
                module: 'proformas',
                description: "Proforma {$proforma->number} rejetée.",
                subject: $proforma,
                oldValues: ['status' => $fromStatus],
                newValues: [
                    'status' => DocumentStatus::Rejected->value,
                    'rejected_by' => $user->id,
                    'rejection_reason' => $reason,
                ],
            );

            return $proforma->refresh();
        });
    }
}
