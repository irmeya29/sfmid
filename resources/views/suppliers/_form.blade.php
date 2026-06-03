@csrf
<div class="grid gap-4 lg:grid-cols-3">
    <input name="code" value="{{ old('code', $supplier->code) }}" placeholder="Code auto si vide" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <input name="name" value="{{ old('name', $supplier->name) }}" placeholder="Nom fournisseur" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
    <input name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" placeholder="Contact" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <input name="phone" value="{{ old('phone', $supplier->phone) }}" placeholder="Telephone" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <input name="email" value="{{ old('email', $supplier->email) }}" placeholder="Email" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <input name="ifu" value="{{ old('ifu', $supplier->ifu) }}" placeholder="IFU" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <input name="rccm" value="{{ old('rccm', $supplier->rccm) }}" placeholder="RCCM" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
    <textarea name="address" rows="3" placeholder="Adresse" class="rounded-xl border border-slate-300 px-4 py-3 text-sm lg:col-span-2">{{ old('address', $supplier->address) }}</textarea>
    <textarea name="notes" rows="3" placeholder="Notes" class="rounded-xl border border-slate-300 px-4 py-3 text-sm lg:col-span-3">{{ old('notes', $supplier->notes) }}</textarea>
    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-semibold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))> Actif</label>
</div>
<div class="mt-6">
    <p class="mb-3 text-sm font-bold text-slate-800">Produits associés</p>
    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
        @php($selected = old('product_ids', $supplier->exists ? $supplier->products->pluck('id')->all() : []))
        @foreach($products as $product)
            <label class="flex gap-3 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, $selected, true))><span>{{ $product->code }} - {{ $product->name }}</span></label>
        @endforeach
    </div>
</div>
<div class="mt-6 flex justify-end gap-3"><x-button :href="route('suppliers.index')" tone="secondary" icon="x">Annuler</x-button><x-button type="submit" icon="save">Enregistrer</x-button></div>
