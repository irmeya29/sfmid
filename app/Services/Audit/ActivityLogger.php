<?php

namespace App\Services\Audit;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(
        string $action,
        ?string $module = null,
        ?string $description = null,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();

        return ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
