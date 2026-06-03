@extends('layouts.app')

@section('title', 'Roles | SFMID Gestion')
@section('subtitle', 'Acces et securite')
@section('page-title', 'Roles')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Roles dynamiques</h3>
            <p class="mt-1 text-sm text-slate-500">Groupes de permissions attribuables aux utilisateurs.</p>
        </div>
        @can('create', \App\Models\Role::class)
            <x-button :href="route('roles.create')" icon="shield-plus">Nouveau role</x-button>
        @endcan
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nom ou slug" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left">Role</th>
                    <th class="px-5 py-4 text-left">Utilisateurs</th>
                    <th class="px-5 py-4 text-left">Permissions</th>
                    <th class="px-5 py-4 text-left">Statut</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($roles as $role)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ $role->name }}</p>
                            <p class="text-xs text-slate-500">{{ $role->slug }} @if($role->is_system) - systeme @endif</p>
                        </td>
                        <td class="px-5 py-4">{{ $role->users_count }}</td>
                        <td class="px-5 py-4">{{ $role->permissions_count }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $role->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $role->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                <x-action-button :href="route('roles.show', $role)" icon="eye" label="Voir le role" />
                                @can('update', $role)
                                    <x-action-button :href="route('roles.edit', $role)" icon="pencil" label="Modifier le role" tone="info" />
                                @endcan
                                @can('assignPermissions', $role)
                                    <x-action-button :href="route('roles.permissions', $role)" icon="list-checks" label="Gerer les permissions" tone="success" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucun role trouve.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-4">{{ $roles->links() }}</div>
    </div>
@endsection
