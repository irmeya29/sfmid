@extends('layouts.app')

@section('title', 'Mon profil | SFMID Gestion')
@section('subtitle', 'Compte utilisateur')
@section('page-title', 'Mon profil')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <div class="space-y-6">
            <x-card title="Informations personnelles" subtitle="Ces informations sont utilisées sur votre compte SFMID.">
                <form method="POST" action="{{ route('profile.update') }}" class="grid gap-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Nom complet</label>
                            <input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            @error('name')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Téléphone</label>
                            <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            @error('phone')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-bold text-slate-700">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            @error('email')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-button type="submit">Enregistrer le profil</x-button>
                    </div>
                </form>
            </x-card>

            <x-card title="Sécurité" subtitle="Changez votre mot de passe sans modifier le reste du profil.">
                <form method="POST" action="{{ route('profile.update') }}" class="grid gap-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
                    <input type="hidden" name="email" value="{{ old('email', $user->email) }}">
                    <input type="hidden" name="phone" value="{{ old('phone', $user->phone) }}">

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Mot de passe actuel</label>
                            <input type="password" name="current_password" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            @error('current_password')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Nouveau mot de passe</label>
                            <input type="password" name="password" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            @error('password')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Confirmation</label>
                            <input type="password" name="password_confirmation" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-button type="submit" tone="secondary">Changer le mot de passe</x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950 text-2xl font-black text-white shadow-sm">
                        {{ str($user->name)->substr(0, 1)->upper() }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-lg font-black text-slate-950">{{ $user->name }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between border-t border-slate-100 pt-3">
                        <span class="text-slate-500">Statut</span>
                        <x-badge :tone="$user->is_active ? 'green' : 'red'">{{ $user->is_active ? 'Actif' : 'Inactif' }}</x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Dernière connexion</span>
                        <span class="font-bold">{{ $user->last_login_at?->format('d/m/Y H:i') ?: '-' }}</span>
                    </div>
                </div>
            </x-card>

            <x-card title="Rôles">
                <div class="flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                        <x-badge tone="blue">{{ $role->name }}</x-badge>
                    @empty
                        <span class="text-sm text-slate-500">Aucun rôle attribué.</span>
                    @endforelse
                </div>
            </x-card>

            <x-card title="Activité récente">
                @if($activities->isEmpty())
                    <x-empty-state title="Aucune activité" message="Vos actions récentes apparaîtront ici." class="py-8" />
                @else
                    <div class="space-y-3">
                        @foreach($activities as $activity)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-sm font-black text-slate-900">{{ $activity->description ?: $activity->action }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $activity->module }} · {{ $activity->created_at?->format('d/m/Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </aside>
    </div>
@endsection
