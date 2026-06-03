<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserPermissionsRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermissionOverride;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? [];
        $this->ensureCanAssignRoles($request, $roleIds);
        $this->ensureCanAssignSuperAdmin($request, $roleIds);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $user->roles()->sync($roleIds);

        $logger->log('created', 'users', "Utilisateur {$user->email} créé.", $user, newValues: [
            'user' => $user->only(['name', 'email', 'phone', 'is_active']),
            'role_ids' => $roleIds,
        ]);

        return redirect()->route('users.show', $user)->with('success', 'Utilisateur créé avec succès.');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        $user->load(['roles.permissions', 'permissionOverrides.permission', 'permissionOverrides.creator']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        $user->load('roles');

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? [];
        $this->ensureCanAssignRoles($request, $roleIds, $user);
        $this->ensureCanAssignSuperAdmin($request, $roleIds);

        $oldValues = [
            'user' => $user->only(['name', 'email', 'phone', 'is_active']),
            'role_ids' => $user->roles()->pluck('roles.id')->all(),
        ];

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();
        $user->roles()->sync($roleIds);

        $logger->log('updated', 'users', "Utilisateur {$user->email} modifié.", $user, $oldValues, [
            'user' => $user->fresh()->only(['name', 'email', 'phone', 'is_active']),
            'role_ids' => $roleIds,
        ]);

        return redirect()->route('users.show', $user)->with('success', 'Utilisateur modifié avec succès.');
    }

    public function toggle(User $user, Request $request, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('disable', $user);

        $old = $user->is_active;
        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $logger->log('status_changed', 'users', "Statut utilisateur {$user->email} modifié.", $user, ['is_active' => $old], ['is_active' => $user->is_active]);

        return back()->with('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }

    public function destroy(User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $oldValues = [
            'user' => $user->only(['name', 'email', 'phone', 'is_active']),
            'role_ids' => $user->roles()->pluck('roles.id')->all(),
        ];

        $user->delete();

        $logger->log('deleted', 'users', "Utilisateur {$user->email} supprimé.", $user, $oldValues);

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé.');
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $user->forceFill(['password' => Hash::make($request->validated('password'))])->save();

        $logger->log('password_reset', 'users', "Mot de passe réinitialisé pour {$user->email}.", $user);

        return back()->with('success', 'Mot de passe réinitialisé.');
    }

    public function permissions(User $user): View
    {
        Gate::authorize('assignPermissions', $user);

        $user->load(['permissionOverrides.permission', 'roles.permissions']);

        return view('users.permissions', [
            'user' => $user,
            'permissionsByModule' => Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module'),
        ]);
    }

    public function updatePermissions(UpdateUserPermissionsRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('assignPermissions', $user);

        $oldValues = $user->permissionOverrides()->with('permission')->get()->mapWithKeys(fn ($override) => [
            $override->permission->slug => $override->is_allowed ? 'allow' : 'deny',
        ])->all();

        $overrides = collect($request->validated('overrides', []))->filter();

        $user->permissionOverrides()->delete();

        foreach ($overrides as $permissionId => $value) {
            UserPermissionOverride::query()->create([
                'user_id' => $user->id,
                'permission_id' => (int) $permissionId,
                'is_allowed' => $value === 'allow',
                'created_by' => $request->user()->id,
                'reason' => $request->validated('reason'),
            ]);
        }

        $newValues = $user->permissionOverrides()->with('permission')->get()->mapWithKeys(fn ($override) => [
            $override->permission->slug => $override->is_allowed ? 'allow' : 'deny',
        ])->all();

        $logger->log('permissions_overridden', 'permissions', "Exceptions de permissions modifiées pour {$user->email}.", $user, $oldValues, $newValues);

        return redirect()->route('users.show', $user)->with('success', 'Exceptions de permissions enregistrées.');
    }

    private function ensureCanAssignSuperAdmin(Request $request, array $roleIds): void
    {
        $hasSuperAdmin = Role::query()->whereIn('id', $roleIds)->where('slug', 'super-admin')->exists();

        if ($hasSuperAdmin && ! $request->user()->hasPermission('sensitive.create_super_admin')) {
            abort(403, 'Création ou attribution super admin non autorisée.');
        }
    }

    private function ensureCanAssignRoles(Request $request, array $roleIds, ?User $user = null): void
    {
        if ($roleIds === []) {
            return;
        }

        if (! $request->user()->hasAnyPermission(['users.assign_roles', 'sensitive.modify_roles_permissions'])) {
            abort(403, 'Attribution de roles non autorisee.');
        }

        if ($user !== null) {
            Gate::authorize('assignRoles', $user);
        }
    }
}
