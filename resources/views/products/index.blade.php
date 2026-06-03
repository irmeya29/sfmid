@extends('layouts.app')

@section('title', 'Produits | SFMID Gestion')
@section('subtitle', 'Catalogue et stock')
@section('page-title', 'Produits')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Liste des produits</h3>
            <p class="mt-1 text-sm text-slate-500">Articles, prix, stock physique, stock en suspens et seuils d'alerte.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            @can('create', \App\Models\ProductCategory::class)
                <x-button :href="route('product-categories.index')" tone="secondary" icon="tags">Categories</x-button>
            @endcan

            @can('create', \App\Models\Product::class)
                <x-button :href="route('products.create')" icon="package-plus">Nouveau produit</x-button>
            @endcan
        </div>
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Code, nom, marque, reference..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Categorie</label>
                <select name="category" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
                    <option value="">Toutes</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Type stock</label>
                <select name="stock_kind" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
                    <option value="">Tous</option>
                    @foreach($stockKinds as $stockKind)
                        <option value="{{ $stockKind['value'] }}" @selected($filters['stock_kind'] === $stockKind['value'])>{{ $stockKind['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
                    <option value="">Tous</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('products.index')" tone="secondary" icon="rotate-ccw">Reinitialiser</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Produit</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Categorie</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Prix vente</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Stock physique</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Suspens</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Statut</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->code }} - {{ $product->unit }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $product->category?->name ?? 'Non classe' }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format((float) $product->sale_price, 0, ',', ' ') }} FCFA</td>
                            <td class="px-5 py-4 text-right">
                                <span class="font-semibold {{ $product->isLowStock() ? 'text-red-600' : 'text-slate-950' }}">{{ \App\Support\NumberFormatter::quantity($product->physical_stock) }}</span>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-[#2676B3]">{{ \App\Support\NumberFormatter::quantity($product->suspense_stock) }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $product->status->badgeClasses() }}">{{ $product->status->label() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <x-action-button :href="route('products.show', $product)" icon="eye" label="Voir le produit" />
                                    @can('update', $product)
                                        <x-action-button :href="route('products.edit', $product)" icon="pencil" label="Modifier le produit" tone="info" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucun produit trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">{{ $products->links() }}</div>
    </div>
@endsection
