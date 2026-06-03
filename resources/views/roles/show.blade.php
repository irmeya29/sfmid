@extends('layouts.app')

@section('title', 'Rôle | SFMID Gestion')
@section('subtitle', 'Accès et sécurité')
@section('page-title', $role->name)

@section('content')
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm text-slate-500">{{ $role->is_system ? 'Rôle système' : 'Rôle personnalisé' }}</p>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $role->description }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $role)
                <x-button :href="route('roles.edit', $role)" tone="secondary" icon="pencil">Modifier</x-button>
            @endcan
            @can('assignPermissions', $role)
                <a href="{{ route('roles.permissions', $role) }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Permissions</a>
            @endcan
            @can('delete', $role)
                <form method="POST" action="{{ route('roles.destroy', $role) }}">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" tone="danger" icon="trash-2">Supprimer</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
            <h3 class="text-lg font-bold text-slate-950">Permissions</h3>
            <div class="mt-4 space-y-5">
                @forelse($role->permissions->sortBy('module')->groupBy('module') as $permissions)
                    <div>
                        <p class="mb-2 text-sm font-black uppercase text-slate-500">{{ $permissions->first()->moduleLabel() }}</p>
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach($permissions->sortBy('action') as $permission)
                                <div class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-semibold text-slate-800">{{ $permission->actionLabel() }}</span>
                                        @if($permission->is_sensitive)
                                            <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">Sensible</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $permission->helpText() }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aucune permission attribuée.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Utilisateurs</h3>
            <div class="mt-4 space-y-3">
                @forelse($role->users as $user)
                    <a href="{{ route('users.show', $user) }}" class="block rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800">{{ $user->name }}</a>
                @empty
                    <p class="text-sm text-slate-500">Aucun utilisateur.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
