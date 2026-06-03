<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Authentification requise.');
        }

        $requiredPermissions = collect($permissions)
            ->flatMap(fn (string $permission): array => explode('|', $permission))
            ->map(fn (string $permission): string => trim($permission))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($requiredPermissions === []) {
            abort(403, 'Permission non configurée.');
        }

        if (! $user->hasAnyPermission($requiredPermissions)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
