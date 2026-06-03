@extends('layouts.app')

@section('title', 'Proformas | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Proformas')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Liste des proformas</h3>
            <p class="mt-1 text-sm text-slate-500">
                Création, soumission, validation, rejet et conversion en bordereau de livraison.
            </p>
        </div>

        @can('create', \App\Models\Proforma::class)
            <x-button :href="route('proformas.create')" icon="file-plus-2">Nouvelle proforma</x-button>
        @endcan
    </div>

    <form method="GET" action="{{ route('proformas.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Numéro proforma ou client..."
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                    <option value="">Tous</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>
                            {{ $status['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('proformas.index')" tone="secondary" icon="rotate-ccw">Réinitialiser</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Numéro</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Client</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Date</th>
                        <th class="px-5 py-4 text-right font-bold text-slate-600">Total</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Statut</th>
                        <th class="px-5 py-4 text-right font-bold text-slate-600">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($proformas as $proforma)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $proforma->number }}</p>
                                <p class="text-xs text-slate-500">
                                    Créée par {{ $proforma->creator?->name ?? 'N/A' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $proforma->client?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $proforma->client?->code }}</p>
                            </td>

                            <td class="px-5 py-4 text-slate-600">
                                {{ $proforma->issue_date?->format('d/m/Y') }}
                            </td>

                            <td class="px-5 py-4 text-right font-bold text-slate-950">
                                {{ number_format((float) $proforma->total, 0, ',', ' ') }} FCFA
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $proforma->status->badgeClasses() }}">
                                    {{ $proforma->status->label() }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <x-action-button :href="route('proformas.show', $proforma)" icon="eye" label="Voir la proforma" />

                                    @can('exportPdf', $proforma)
                                        <x-action-button :href="route('proformas.pdf', $proforma)" target="_blank" icon="printer" label="Imprimer / PDF" tone="neutral" />
                                    @endcan

                                    @can('update', $proforma)
                                        <x-action-button :href="route('proformas.edit', $proforma)" icon="pencil" label="Modifier la proforma" tone="info" />
                                    @endcan

                                    @can('submit', $proforma)
                                        <form method="POST" action="{{ route('proformas.submit', $proforma) }}" class="inline-flex">
                                            @csrf
                                            <x-action-button type="submit" icon="send" label="Soumettre en validation" tone="warning" />
                                        </form>
                                    @endcan

                                    @can('validate', $proforma)
                                        <form method="POST" action="{{ route('proformas.validate', $proforma) }}" class="inline-flex">
                                            @csrf
                                            <x-action-button type="submit" icon="check" label="Valider la proforma" tone="success" />
                                        </form>
                                    @endcan

                                    @can('reject', $proforma)
                                        <x-action-button :href="route('proformas.show', $proforma)" icon="x" label="Rejeter avec motif" tone="danger" />
                                    @endcan

                                    @can('convertToDeliveryNote', $proforma)
                                        <form method="POST" action="{{ route('proformas.convert-to-delivery-note', $proforma) }}" class="inline-flex">
                                            @csrf
                                            <x-action-button type="submit" icon="truck" label="Convertir en BL" tone="success" />
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                Aucune proforma trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $proformas->links() }}
        </div>
    </div>
@endsection
