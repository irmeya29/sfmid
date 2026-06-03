@extends('layouts.app')

@section('title', 'Utilisateurs | SFMID Gestion')
@section('subtitle', 'Acces et securite')
@section('page-title', 'Utilisateurs')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Liste des utilisateurs</h3>
            <p class="mt-1 text-sm text-slate-500">Comptes, roles et statut d'acces.</p>
        </div>
        @can('create', \App\Models\User::class)
            <x-button :href="route('users.create')" icon="user-plus">Nouvel utilisateur</x-button>
        @endcan
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nom, email ou telephone" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left">Utilisateur</th>
                    <th class="px-5 py-4 text-left">Roles</th>
                    <th class="px-5 py-4 text-left">Statut</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->roles as $role)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                <x-action-button :href="route('users.show', $user)" icon="eye" label="Voir l'utilisateur" />
                                @can('update', $user)
                                    <x-action-button :href="route('users.edit', $user)" icon="pencil" label="Modifier l'utilisateur" tone="info" />
                                @endcan
                                @can('assignPermissions', $user)
                                    <x-action-button :href="route('users.permissions', $user)" icon="list-checks" label="Permissions specifiques" tone="success" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Aucun utilisateur trouve.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-4">{{ $users->links() }}</div>
    </div>
@endsection
