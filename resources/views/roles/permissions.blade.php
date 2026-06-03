@extends('layouts.app')

@section('title', 'Permissions rôle | SFMID Gestion')
@section('subtitle', 'Accès et sécurité')
@section('page-title', 'Permissions du rôle')

@section('content')
    @php
        $selectedPermissions = $role->permissions->pluck('id')->all();
    @endphp

    <form method="POST" action="{{ route('roles.permissions.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">{{ $role->name }}</h3>
            <p class="mt-1 text-sm text-slate-500">Cochez les droits métier attribués à ce rôle.</p>
        </section>

        @foreach($permissionsByModule as $module => $permissions)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-bold text-slate-950">{{ $permissions->first()->moduleLabel() }}</h3>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($permissions as $permission)
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 text-sm">
                            <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" class="mt-1 h-4 w-4 rounded border-slate-300" @checked(in_array($permission->id, old('permission_ids', $selectedPermissions), true))>
                            <span>
                                <span class="block font-semibold text-slate-800">{{ $permission->actionLabel() }}</span>
                                <span class="text-xs text-slate-500">{{ $permission->helpText() }}</span>
                                @if($permission->is_sensitive)
                                    <span class="mt-2 inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">Sensible</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end gap-3">
            <a href="{{ route('roles.show', $role) }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Annuler</a>
            <x-button type="submit" icon="save">Enregistrer</x-button>
        </div>
    </form>
@endsection
