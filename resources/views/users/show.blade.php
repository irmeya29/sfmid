@extends('layouts.app')

@section('title', 'Utilisateur | SFMID Gestion')
@section('subtitle', 'Accès et sécurité')
@section('page-title', $user->name)

@section('content')
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm text-slate-500">{{ $user->email }}</p>
            <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $user->is_active ? 'Actif' : 'Inactif' }}
            </span>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $user)
                <x-button :href="route('users.edit', $user)" tone="secondary" icon="pencil">Modifier</x-button>
            @endcan
            @can('assignPermissions', $user)
                <a href="{{ route('users.permissions', $user) }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Exceptions permissions</a>
            @endcan
            @can('disable', $user)
                <form method="POST" action="{{ route('users.toggle', $user) }}">
                    @csrf
                    <x-button type="submit" tone="secondary" :icon="$user->is_active ? 'user-x' : 'user-check'">{{ $user->is_active ? 'Desactiver' : 'Activer' }}</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
            <h3 class="text-lg font-bold text-slate-950">Rôles attribués</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @forelse($user->roles as $role)
                    <a href="{{ route('roles.show', $role) }}" class="rounded-xl border border-slate-200 p-4">
                        <p class="font-bold text-slate-900">{{ $role->name }}</p>
                        <p class="text-xs text-slate-500">{{ $role->slug }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Aucun rôle attribué.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Reset mot de passe</h3>
            @can('resetPassword', $user)
                <form method="POST" action="{{ route('users.reset-password', $user) }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="relative">
                        <input id="reset-password" type="password" name="password" placeholder="Nouveau mot de passe" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-12 text-sm" required>
                        <button type="button" data-password-toggle="reset-password" class="absolute inset-y-0 right-3 flex items-center rounded-lg px-2 text-slate-400 hover:text-slate-950" aria-label="Afficher le mot de passe">
                            <i data-lucide="eye" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <div class="relative">
                        <input id="reset-password-confirmation" type="password" name="password_confirmation" placeholder="Confirmation" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-12 text-sm" required>
                        <button type="button" data-password-toggle="reset-password-confirmation" class="absolute inset-y-0 right-3 flex items-center rounded-lg px-2 text-slate-400 hover:text-slate-950" aria-label="Afficher la confirmation">
                            <i data-lucide="eye" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <x-button type="submit" icon="key-round" class="w-full">Reinitialiser</x-button>
                </form>
            @else
                <p class="mt-4 text-sm text-slate-500">Action non autorisee.</p>
            @endcan
        </section>
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Exceptions de permissions</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse($user->permissionOverrides as $override)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold">{{ $override->permission?->displayLabel() }}</p>
                                @if($override->permission?->is_sensitive)
                                    <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Sensible</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $override->is_allowed ? 'Autoriser' : 'Refuser' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $override->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-slate-500">Aucune exception.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
