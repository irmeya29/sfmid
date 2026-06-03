@csrf

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="text-sm font-semibold text-slate-700">Nom du rôle</label>
        <input type="text" name="name" value="{{ old('name', $role->name) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm font-semibold text-slate-700">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $role->slug) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="généré automatiquement si vide">
        @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label class="text-sm font-semibold text-slate-700">Description</label>
        <textarea name="description" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('description', $role->description) }}</textarea>
    </div>

    <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300" @checked(old('is_active', $role->is_active ?? true))>
        Rôle actif
    </label>
</div>

<div class="mt-6 space-y-5">
    <h3 class="text-lg font-bold text-slate-950">Permissions par module</h3>
    @php
        $selectedPermissions = old('permission_ids', $role->exists ? $role->permissions->pluck('id')->all() : []);
    @endphp
    @foreach($permissionsByModule as $module => $permissions)
        <section class="rounded-xl border border-slate-200 p-4">
            <p class="mb-3 font-bold text-slate-900">{{ $permissions->first()->moduleLabel() }}</p>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($permissions as $permission)
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" class="mt-1 h-4 w-4 rounded border-slate-300" @checked(in_array($permission->id, $selectedPermissions, true))>
                        <span>
                            <span class="block font-semibold text-slate-800">{{ $permission->actionLabel() }}</span>
                            <span class="text-xs text-slate-500">{{ $permission->helpText() }}</span>
                            @if($permission->is_sensitive)
                                <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Sensible</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('roles.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Annuler</a>
    <x-button type="submit" icon="save">Enregistrer</x-button>
</div>
