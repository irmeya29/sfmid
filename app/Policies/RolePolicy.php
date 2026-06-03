<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.update')
            && ! $role->isSystemRole();
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.delete')
            && ! $role->isSystemRole()
            && ! $role->users()->exists();
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.assign_permissions')
            || $user->hasPermission('sensitive.modify_roles_permissions');
    }
}
