@csrf
<div class="grid gap-4 lg:grid-cols-2">
    <input name="name" value="{{ old('name', $category->name) }}" placeholder="Nom catégorie" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="Slug auto si vide" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <textarea name="description" rows="3" placeholder="Description" class="rounded-xl border border-slate-300 px-4 py-3 text-sm lg:col-span-2">{{ old('description', $category->description) }}</textarea>
    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-semibold"><input type="hidden" name="is_sensitive" value="0"><input type="checkbox" name="is_sensitive" value="1" @checked(old('is_sensitive', $category->is_sensitive))> Charge sensible</label>
    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-semibold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Active</label>
</div>
<div class="mt-6 flex justify-end gap-3"><x-button :href="route('expense-categories.index')" tone="secondary" icon="x">Annuler</x-button><x-button type="submit" icon="save">Enregistrer</x-button></div>
