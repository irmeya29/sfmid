@extends('layouts.app')

@section('title', $title.' | SFMID Gestion')
@section('subtitle', 'Gestion du stock')
@section('page-title', $title)

@section('content')
    @include('stock._nav')

    <form method="POST" action="{{ $action }}" class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Produit</label>
                <select name="product_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Sélectionner</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->code }} - {{ $product->name }} | Physique {{ \App\Support\NumberFormatter::quantity($product->physical_stock) }} | Outil {{ \App\Support\NumberFormatter::quantity($product->tool_stock) }}</option>
                    @endforeach
                </select>
                @error('product_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Site de stock</label>
                <select name="stock_site_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @foreach($stockSites as $site)
                        <option value="{{ $site->id }}" @selected((string) old('stock_site_id', $defaultStockSiteId) === (string) $site->id)>
                            {{ $site->name }} @if($site->can_sell) - vente possible @endif
                        </option>
                    @endforeach
                </select>
                @error('stock_site_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                <select name="type" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $defaultType) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Stock concerné</label>
                <select name="stock_column" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="physical_stock" @selected(old('stock_column', $defaultStockColumn) === 'physical_stock')>Stock physique</option>
                    <option value="tool_stock" @selected(old('stock_column', $defaultStockColumn) === 'tool_stock')>Stock outil</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Quantité</label>
                <input type="number" step="any" min="0.001" name="quantity" value="{{ old('quantity', 1) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('quantity')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Coût unitaire</label>
                <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Motif</label>
                <textarea name="reason" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('reason') }}</textarea>
                <p class="mt-2 text-sm text-slate-500">Obligatoire pour sorties, pertes/casses et ajustements. Les ajustements et pertes/casses seront soumis à validation.</p>
                @error('reason')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <x-button type="submit" icon="save">Enregistrer</x-button>
            <a href="{{ route('stock.movements') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Annuler</a>
        </div>
    </form>
@endsection
