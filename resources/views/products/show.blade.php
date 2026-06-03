@extends('layouts.app')

@section('title', $product->name.' | SFMID Gestion')
@section('subtitle', 'Fiche produit')
@section('page-title', $product->name)

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour aux produits
            </a>
            <p class="mt-3 text-sm text-slate-500">Code produit : <span class="font-bold text-slate-700">{{ $product->code }}</span></p>
        </div>

        <div class="flex flex-wrap gap-3">
            @can('update', $product)
                <x-button :href="route('products.edit', $product)" icon="pencil">Modifier</x-button>
            @endcan

            @can('delete', $product)
                <form method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Supprimer définitivement ce produit ?">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" tone="danger" icon="trash-2">Supprimer</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-base font-bold text-slate-950">Informations produit</h3>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nom</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $product->name }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catégorie</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $product->category?->name ?? 'Non classé' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marque</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $product->brand ?: 'Non renseignée' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Référence interne SFMID</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $product->internal_reference ?: 'Non renseignée' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Référence fournisseur</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $product->supplier_reference ?: 'Non renseignée' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unité</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $product->unit }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Type de stock</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $product->stock_kind->label() }}</p>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $product->description ?: 'Non renseignée' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-950">Statut</h3>

            <div class="mt-5 space-y-4">
                <div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $product->status->badgeClasses() }}">
                        {{ $product->status->label() }}
                    </span>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Prix d’achat</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ number_format((float) $product->purchase_price, 0, ',', ' ') }} FCFA</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Prix de vente</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ number_format((float) $product->sale_price, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-950">Références client / mine</h3>
                    <p class="mt-1 text-sm text-slate-500">Ces références seront affichées dans les documents commerciaux du client concerné.</p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Client / Mine</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Référence client</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Appellation client</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Prix</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($product->clientPrices as $reference)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-950">{{ $reference->client?->name }}</td>
                                <td class="px-4 py-3">{{ $reference->client_reference ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $reference->client_designation ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $reference->sale_price, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3 text-right">
                                    @can('update', $product)
                                        <form method="POST" action="{{ route('products.client-references.destroy', [$product, $reference]) }}" class="inline-flex">
                                            @csrf
                                            @method('DELETE')
                                            <x-action-button type="submit" icon="trash-2" label="Supprimer la référence client" tone="danger" />
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Aucune référence client/mine enregistrée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @can('update', $product)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-950">Ajouter une référence</h3>
                <form method="POST" action="{{ route('products.client-references.store', $product) }}" class="mt-5 space-y-4">
                    @csrf
                    <select name="client_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <option value="">Client / mine</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} - {{ $client->code }}</option>
                        @endforeach
                    </select>
                    <input name="client_reference" required placeholder="Référence client / mine" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <input name="client_designation" placeholder="Appellation client si différente" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <input type="number" min="0" step="0.01" name="sale_price" placeholder="Prix spécifique optionnel" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <input type="number" min="0" max="100" step="0.01" name="discount_rate" placeholder="Remise % optionnelle" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <x-button type="submit" icon="save" class="w-full">Enregistrer</x-button>
                </form>
            </div>
        @endcan
    </div>

    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Stock physique</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ \App\Support\NumberFormatter::quantity($product->physical_stock) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Stock réservé</p>
            <p class="mt-3 text-3xl font-bold text-amber-600">{{ \App\Support\NumberFormatter::quantity($product->reserved_stock) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Stock en suspens</p>
            <p class="mt-3 text-3xl font-bold text-indigo-600">{{ \App\Support\NumberFormatter::quantity($product->suspense_stock) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Seuil d’alerte</p>
            <p class="mt-3 text-3xl font-bold {{ $product->isLowStock() ? 'text-red-600' : 'text-slate-950' }}">
                {{ \App\Support\NumberFormatter::quantity($product->alert_threshold) }}
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-950">Derniers mouvements de stock</h3>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-slate-600">Date</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-600">Type</th>
                        <th class="px-4 py-3 text-right font-bold text-slate-600">Quantité</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-600">Motif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($product->stockMovements as $movement)
                        <tr>
                            <td class="px-4 py-3 text-slate-600">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $movement->type->label() }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ \App\Support\NumberFormatter::quantity($movement->quantity) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $movement->reason ?: 'Non renseigné' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                Aucun mouvement enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
