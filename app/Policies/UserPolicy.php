<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $targetUser): bool
    {
        if (! $user->hasPermission('users.update')) {
            return false;
        }

        if ($targetUser->hasRole('super-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $targetUser): bool
    {
        if (! $user->hasPermission('users.delete')) {
            return false;
        }

        if ($user->id === $targetUser->id) {
            return false;
        }

        if ($targetUser->hasRole('super-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return true;
    }

    public function disable(User $user, User $targetUser): bool
    {
        if (! $user->hasPermission('users.disable')) {
            return false;
        }

        if ($user->id === $targetUser->id) {
            return false;
        }

        if ($targetUser->hasRole('super-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return true;
    }

    public function assignRoles(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.assign_roles')
            || $user->hasPermission('sensitive.modify_roles_permissions');
    }

    public function assignPermissions(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.assign_permissions')
            || $user->hasPermission('sensitive.modify_roles_permissions');
    }

    public function resetPassword(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.reset_password');
    }
}
