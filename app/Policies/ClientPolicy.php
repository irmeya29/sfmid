<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('clients.create');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.update');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.delete');
    }

    public function disable(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.disable');
    }

    public function viewBalance(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.view_balance');
    }

    public function viewHistory(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.view_history');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('clients.export');
    }
}
