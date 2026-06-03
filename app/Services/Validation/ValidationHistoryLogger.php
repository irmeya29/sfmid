<?php

namespace App\Services\Validation;

use App\Enums\ValidationAction;
use App\Models\ValidationHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ValidationHistoryLogger
{
    public function log(
        Model $document,
        ValidationAction $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $reason = null,
        ?string $comment = null,
    ): ValidationHistory {
        return ValidationHistory::query()->create([
            'document_type' => $document::class,
            'document_id' => $document->getKey(),
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'comment' => $comment,
            'user_id' => Auth::id(),
        ]);
    }
}
