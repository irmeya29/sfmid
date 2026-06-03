@extends('layouts.app')

@section('title', 'Categories produits | SFMID Gestion')
@section('subtitle', 'Catalogue produits')
@section('page-title', 'Categories produits')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Liste des categories</h3>
            <p class="mt-1 text-sm text-slate-500">Organisation des familles de produits et du catalogue.</p>
        </div>

        @can('create', \App\Models\ProductCategory::class)
            <x-button :href="route('product-categories.create')" icon="plus">Nouvelle categorie</x-button>
        @endcan
    </div>

    <form method="GET" action="{{ route('product-categories.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Nom ou slug..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
            </div>
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('product-categories.index')" tone="secondary" icon="rotate-ccw">Reinitialiser</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Nom</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Parent</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Produits</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Statut</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $category->name }}</p>
                                <p class="text-xs text-slate-500">{{ $category->slug }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $category->parent?->name ?? 'Aucune' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ number_format($category->products_count, 0, ',', ' ') }}</td>
                            <td class="px-5 py-4">
                                <x-badge :tone="$category->is_active ? 'green' : 'slate'">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @can('update', $category)
                                        <x-action-button :href="route('product-categories.edit', $category)" icon="pencil" label="Modifier la categorie" tone="info" />
                                    @endcan
                                    @can('delete', $category)
                                        <form method="POST" action="{{ route('product-categories.destroy', $category) }}" class="inline-flex" onsubmit="return confirm('Supprimer cette categorie ?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-action-button type="submit" icon="trash-2" label="Supprimer la categorie" tone="danger" />
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucune categorie trouvee.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">{{ $categories->links() }}</div>
    </div>
@endsection
