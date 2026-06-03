<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('roles.index', [
            'roles' => $roles,
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Role::class);

        return view('roles.create', [
            'role' => new Role(['is_active' => true]),
            'permissionsByModule' => $this->permissionsByModule(),
        ]);
    }

    public function store(StoreRoleRequest $request, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $data = $request->validated();
        $permissionIds = $data['permission_ids'] ?? [];

        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $role->permissions()->sync($permissionIds);

        $logger->log('created', 'roles', "Role {$role->slug} cree.", $role, newValues: [
            'role' => $role->only(['name', 'slug', 'description', 'is_active']),
            'permission_ids' => $permissionIds,
        ]);

        return redirect()->route('roles.show', $role)->with('success', 'Role cree avec succes.');
    }

    public function show(Role $role): View
    {
        Gate::authorize('view', $role);

        $role->load(['permissions', 'users']);

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        Gate::authorize('update', $role);

        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,
            'permissionsByModule' => $this->permissionsByModule(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $role);

        $data = $request->validated();
        $permissionIds = $data['permission_ids'] ?? [];

        $oldValues = [
            'role' => $role->only(['name', 'slug', 'description', 'is_active']),
            'permission_ids' => $role->permissions()->pluck('permissions.id')->all(),
        ];

        $role->forceFill([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        $role->permissions()->sync($permissionIds);

        $logger->log('updated', 'roles', "Role {$role->slug} modifie.", $role, $oldValues, [
            'role' => $role->fresh()->only(['name', 'slug', 'description', 'is_active']),
            'permission_ids' => $permissionIds,
        ]);

        return redirect()->route('roles.show', $role)->with('success', 'Role modifie avec succes.');
    }

    public function destroy(Role $role, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('delete', $role);

        $oldValues = $role->only(['name', 'slug', 'description', 'is_active']);
        $role->delete();

        $logger->log('deleted', 'roles', "Role {$role->slug} supprime.", $role, $oldValues);

        return redirect()->route('roles.index')->with('success', 'Role supprime.');
    }

    public function permissions(Role $role): View
    {
        Gate::authorize('assignPermissions', $role);

        $role->load('permissions');

        return view('roles.permissions', [
            'role' => $role,
            'permissionsByModule' => $this->permissionsByModule(),
        ]);
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('assignPermissions', $role);

        $oldValues = $role->permissions()->pluck('permissions.slug')->all();
        $permissionIds = $request->validated('permission_ids', []);

        $role->permissions()->sync($permissionIds);

        $newValues = $role->permissions()->pluck('permissions.slug')->all();

        $logger->log('permissions_synced', 'permissions', "Permissions modifiees pour le role {$role->slug}.", $role, [
            'permissions' => $oldValues,
        ], [
            'permissions' => $newValues,
        ]);

        return redirect()->route('roles.show', $role)->with('success', 'Permissions du role enregistrees.');
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, Permission>>
     */
    private function permissionsByModule()
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('action')
            ->get()
            ->groupBy('module');
    }
}
