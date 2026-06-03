@extends('layouts.app')

@section('title', 'Exceptions utilisateur | SFMID Gestion')
@section('subtitle', 'Accès et sécurité')
@section('page-title', 'Exceptions permissions')

@section('content')
    @php
        $current = $user->permissionOverrides->mapWithKeys(fn ($override) => [$override->permission_id => $override->is_allowed ? 'allow' : 'deny']);
    @endphp

    <form method="POST" action="{{ route('users.permissions.update', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-[1fr_2fr]">
                <div>
                    <h3 class="text-lg font-bold text-slate-950">{{ $user->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Choisir hériter, autoriser ou refuser pour chaque droit métier.</p>
                </div>
                <textarea name="reason" rows="3" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Motif de modification">{{ old('reason') }}</textarea>
            </div>
        </section>

        @foreach($permissionsByModule as $module => $permissions)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-bold text-slate-950">{{ $permissions->first()->moduleLabel() }}</h3>
                <div class="grid gap-3">
                    @foreach($permissions as $permission)
                        @php
                            $value = old("overrides.{$permission->id}", $current[$permission->id] ?? '');
                        @endphp
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $permission->actionLabel() }}</p>
                                <p class="text-xs text-slate-500">{{ $permission->helpText() }}</p>
                                @if($permission->is_sensitive)
                                    <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Sensible</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm">
                                <label class="flex items-center gap-2"><input type="radio" name="overrides[{{ $permission->id }}]" value="" @checked($value === '')> Hériter</label>
                                <label class="flex items-center gap-2"><input type="radio" name="overrides[{{ $permission->id }}]" value="allow" @checked($value === 'allow')> Autoriser</label>
                                <label class="flex items-center gap-2"><input type="radio" name="overrides[{{ $permission->id }}]" value="deny" @checked($value === 'deny')> Refuser</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.show', $user) }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Annuler</a>
            <x-button type="submit" icon="save">Enregistrer</x-button>
        </div>
    </form>
@endsection
