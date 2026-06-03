@extends('layouts.app')

@section('title', 'Categories charges | SFMID')
@section('subtitle', 'Tresorerie')
@section('page-title', 'Categories de charges')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Categories de charges</h3>
            <p class="mt-1 text-sm text-slate-500">Classement des depenses de tresorerie et protection des charges sensibles.</p>
        </div>

        @if(auth()->user()?->hasPermission('expense_categories.create'))
            <x-button :href="route('expense-categories.create')" icon="plus">Nouvelle categorie</x-button>
        @endif
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input name="search" value="{{ $filters['search'] }}" placeholder="Rechercher une categorie" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
            </div>
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('expense-categories.index')" tone="secondary" icon="rotate-ccw">Reinitialiser</x-button>
        </div>
    </form>

    <x-card>
        @if($categories->isEmpty())
            <x-empty-state title="Aucune categorie" message="Les categories permettent de classer les depenses de tresorerie.">
                @if(auth()->user()?->hasPermission('expense_categories.create'))
                    <x-button :href="route('expense-categories.create')" icon="plus">Creer une categorie</x-button>
                @endif
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <x-table>
                    <thead>
                        <tr>
                            <th class="text-left">Categorie</th>
                            <th class="text-left">Statut</th>
                            <th class="text-right">Depenses</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-950">{{ $category->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $category->slug }}</p>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        @if($category->is_sensitive)
                                            <x-badge tone="red">Sensible</x-badge>
                                        @endif
                                        <x-badge :tone="$category->is_active ? 'green' : 'slate'">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-badge>
                                    </div>
                                </td>
                                <td class="text-right font-semibold">{{ $category->expenses_count }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <x-action-button :href="route('expense-categories.show', $category)" icon="eye" label="Voir la categorie" />
                                        @if(auth()->user()?->hasPermission('expense_categories.update'))
                                            <x-action-button :href="route('expense-categories.edit', $category)" icon="pencil" label="Modifier la categorie" tone="info" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">{{ $categories->links() }}</div>
        @endif
    </x-card>
@endsection
