@extends('layouts.app')

@section('title', 'Fournisseurs | SFMID')
@section('subtitle', 'Achats fournisseurs')
@section('page-title', 'Fournisseurs')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Liste des fournisseurs</h3>
            <p class="mt-1 text-sm text-slate-500">Suivi des achats, bons de commande, factures et dettes fournisseurs.</p>
        </div>

        @if(auth()->user()?->hasPermission('suppliers.create'))
            <x-button :href="route('suppliers.create')" icon="user-plus">Nouveau fournisseur</x-button>
        @endif
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input name="search" value="{{ $filters['search'] }}" placeholder="Nom fournisseur, telephone, email..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
            </div>
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('suppliers.index')" tone="secondary" icon="rotate-ccw">Reinitialiser</x-button>
        </div>
    </form>

    <x-card>
        @if($suppliers->isEmpty())
            <x-empty-state title="Aucun fournisseur" message="Creez vos fournisseurs pour suivre les achats, dettes et produits associes.">
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <x-button :href="route('suppliers.create')" icon="user-plus">Creer un fournisseur</x-button>
                @endif
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <x-table>
                    <thead>
                        <tr>
                            <th class="text-left">Fournisseur</th>
                            <th class="text-center">Produits</th>
                            <th class="text-center">BC</th>
                            <th class="text-center">Factures</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suppliers as $supplier)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-950">{{ $supplier->code }} - {{ $supplier->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $supplier->phone ?: '-' }} {{ $supplier->email ?: '' }}</p>
                                </td>
                                <td class="text-center font-semibold">{{ $supplier->products_count }}</td>
                                <td class="text-center font-semibold">{{ $supplier->purchase_orders_count }}</td>
                                <td class="text-center font-semibold">{{ $supplier->invoices_count }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <x-action-button :href="route('suppliers.show', $supplier)" icon="eye" label="Voir le fournisseur" />
                                        @if(auth()->user()?->hasPermission('suppliers.update'))
                                            <x-action-button :href="route('suppliers.edit', $supplier)" icon="pencil" label="Modifier le fournisseur" tone="info" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">{{ $suppliers->links() }}</div>
        @endif
    </x-card>
@endsection
