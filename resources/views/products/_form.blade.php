@php
    $units = [
        'piece' => 'Pièce',
        'meter' => 'Mètre',
        'liter' => 'Litre',
        'kit' => 'Kit',
        'set' => 'Ensemble',
        'box' => 'Boîte',
        'roll' => 'Rouleau',
        'pair' => 'Paire',
        'carton' => 'Carton',
    ];
@endphp

<form method="POST" action="{{ $action }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf

    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Code produit</label>
            <input
                type="text"
                name="code"
                value="{{ old('code', $product->code) }}"
                placeholder="Automatique si vide"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Nom du produit <span class="text-red-600">*</span></label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $product->name) }}"
                required
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</label>
            <select name="product_category_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                <option value="">Non classé</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('product_category_id', $product->product_category_id) === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('product_category_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Unité</label>
            <select name="unit" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                @foreach($units as $value => $label)
                    <option value="{{ $value }}" @selected(old('unit', $product->unit) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('unit')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Marque</label>
            <input
                type="text"
                name="brand"
                value="{{ old('brand', $product->brand) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('brand')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Référence interne SFMID</label>
            <input
                type="text"
                name="internal_reference"
                value="{{ old('internal_reference', $product->internal_reference) }}"
                placeholder="Ex : NOV-HYD-001"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('internal_reference')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Référence fournisseur</label>
            <input
                type="text"
                name="supplier_reference"
                value="{{ old('supplier_reference', $product->supplier_reference) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('supplier_reference')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
            <textarea
                name="description"
                rows="3"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >{{ old('description', $product->description) }}</textarea>
            @error('description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Prix d’achat</label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="purchase_price"
                value="{{ old('purchase_price', $product->purchase_price ?? 0) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('purchase_price')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Prix de vente</label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="sale_price"
                value="{{ old('sale_price', $product->sale_price ?? 0) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('sale_price')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Stock physique initial</label>
            <input
                type="number"
                step="any"
                min="0"
                name="physical_stock"
                value="{{ old('physical_stock', \App\Support\NumberFormatter::quantity($product->physical_stock ?? 0)) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('physical_stock')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Stock outil</label>
            <input
                type="number"
                step="any"
                min="0"
                name="tool_stock"
                value="{{ old('tool_stock', \App\Support\NumberFormatter::quantity($product->tool_stock ?? 0)) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('tool_stock')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Seuil d’alerte</label>
            <input
                type="number"
                step="any"
                min="0"
                name="alert_threshold"
                value="{{ old('alert_threshold', \App\Support\NumberFormatter::quantity($product->alert_threshold ?? 0)) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('alert_threshold')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Type de stock</label>
            <select name="stock_kind" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                @foreach($stockKinds as $stockKind)
                    <option value="{{ $stockKind['value'] }}" @selected(old('stock_kind', $product->stock_kind?->value) === $stockKind['value'])>
                        {{ $stockKind['label'] }}
                    </option>
                @endforeach
            </select>
            @error('stock_kind')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
            <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                @foreach($statuses as $status)
                    <option value="{{ $status['value'] }}" @selected(old('status', $product->status?->value) === $status['value'])>
                        {{ $status['label'] }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Le stock réservé et le stock en suspens ne se modifient pas ici. Ils seront mis à jour par les workflows proforma, BL, facture et paiement.
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit" icon="save">{{ $submitLabel }}</x-button>

        <a href="{{ route('products.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Annuler
        </a>
    </div>
</form>
