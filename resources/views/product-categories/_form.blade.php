<form method="POST" action="{{ $action }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf

    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Catégorie parente</label>
            <select name="parent_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                <option value="">Aucune</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
            @error('parent_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Nom <span class="text-red-600">*</span></label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $category->name) }}"
                required
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
            <textarea
                name="description"
                rows="4"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >{{ old('description', $category->description) }}</textarea>
            @error('description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
            <select name="is_active" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                <option value="1" @selected((string) old('is_active', (int) $category->is_active) === '1')>Active</option>
                <option value="0" @selected((string) old('is_active', (int) $category->is_active) === '0')>Inactive</option>
            </select>
            @error('is_active')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit" icon="save">{{ $submitLabel }}</x-button>

        <a href="{{ route('product-categories.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Annuler
        </a>
    </div>
</form>
