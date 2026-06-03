@csrf

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="text-sm font-semibold text-slate-700">Nom</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm font-semibold text-slate-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm font-semibold text-slate-700">Telephone</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    @if($user->exists)
        <label class="mt-7 flex items-center gap-3 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300" @checked(old('is_active', $user->is_active))>
            Compte actif
        </label>
    @else
        <div>
            <label class="text-sm font-semibold text-slate-700">Mot de passe</label>
            <input type="password" name="password" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="text-sm font-semibold text-slate-700">Confirmation</label>
            <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
        </div>

        <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300" @checked(old('is_active', $user->is_active ?? true))>
            Compte actif
        </label>
    @endif
</div>

<div class="mt-6">
    <p class="mb-3 text-sm font-bold text-slate-800">Roles</p>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach($roles as $role)
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm">
                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="mt-1 h-4 w-4 rounded border-slate-300" @checked(in_array($role->id, old('role_ids', $user->exists ? $user->roles->pluck('id')->all() : []), true))>
                <span>
                    <span class="block font-bold text-slate-900">{{ $role->name }}</span>
                    <span class="text-xs text-slate-500">{{ $role->slug }}</span>
                </span>
            </label>
        @endforeach
    </div>
    @error('role_ids')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Annuler</a>
    <x-button type="submit" icon="save">Enregistrer</x-button>
</div>
