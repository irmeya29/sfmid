@extends('layouts.app')

@section('title', 'Bordereaux de livraison | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Bordereaux de livraison')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Liste des BL</h3>
            <p class="mt-1 text-sm text-slate-500">Suivi des livraisons, validation, préparation et passage en stock en suspens.</p>
        </div>

        @can('create', \App\Models\DeliveryNote::class)
            <x-button :href="route('delivery-notes.create')" icon="file-plus-2">Nouveau BL</x-button>
        @endcan
    </div>

    <form method="GET" action="{{ route('delivery-notes.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Numéro BL ou client..."
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                    <option value="">Tous</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('delivery-notes.index')" tone="secondary" icon="rotate-ccw">Réinitialiser</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Numéro</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Client</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Date prévue</th>
                        <th class="px-5 py-4 text-right font-bold text-slate-600">Total</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-600">Statut</th>
                        <th class="px-5 py-4 text-right font-bold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deliveryNotes as $deliveryNote)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $deliveryNote->number }}</p>
                                <p class="text-xs text-slate-500">
                                    @if($deliveryNote->customerOrder)
                                        BC {{ $deliveryNote->customerOrder->customer_reference ?: $deliveryNote->customerOrder->number }}
                                    @elseif($deliveryNote->proforma)
                                        Depuis {{ $deliveryNote->proforma->number }}
                                    @else
                                        Créé par {{ $deliveryNote->creator?->name ?? 'N/A' }}
                                    @endif
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $deliveryNote->client?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $deliveryNote->client?->code }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $deliveryNote->planned_delivery_date?->format('d/m/Y') ?: 'Non planifiée' }}</td>
                            <td class="px-5 py-4 text-right font-bold text-slate-950">{{ number_format((float) $deliveryNote->total, 0, ',', ' ') }} FCFA</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $deliveryNote->status->badgeClasses() }}">{{ $deliveryNote->status->label() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <x-action-button :href="route('delivery-notes.show', $deliveryNote)" icon="eye" label="Voir le BL" />

                                    @can('exportPdf', $deliveryNote)
                                        <x-action-button :href="route('delivery-notes.pdf', $deliveryNote)" target="_blank" icon="printer" label="Imprimer / PDF" />
                                    @endcan

                                    @can('update', $deliveryNote)
                                        <x-action-button :href="route('delivery-notes.edit', $deliveryNote)" icon="pencil" label="Modifier le BL" tone="info" />
                                    @endcan

                                    @can('submit', $deliveryNote)
                                        <form method="POST" action="{{ route('delivery-notes.submit', $deliveryNote) }}" class="inline-flex">
                                            @csrf
                                            <x-action-button type="submit" icon="send" label="Soumettre en validation" tone="warning" />
                                        </form>
                                    @endcan

                                    @can('validate', $deliveryNote)
                                        <form method="POST" action="{{ route('delivery-notes.validate', $deliveryNote) }}" class="inline-flex">
                                            @csrf
                                            <x-action-button type="submit" icon="check" label="Valider le BL" tone="success" />
                                        </form>
                                    @endcan

                                    @can('reject', $deliveryNote)
                                        <x-action-button :href="route('delivery-notes.show', $deliveryNote)" icon="x" label="Rejeter avec motif" tone="danger" />
                                    @endcan

                                    @can('markPrepared', $deliveryNote)
                                        <form method="POST" action="{{ route('delivery-notes.mark-prepared', $deliveryNote) }}" class="inline-flex">
                                            @csrf
                                            <x-action-button type="submit" icon="package-check" label="Marquer prepare" tone="success" />
                                        </form>
                                    @endcan

                                    @can('markDelivered', $deliveryNote)
                                        <x-action-button :href="route('delivery-notes.show', $deliveryNote)" icon="truck" label="Renseigner reception et livrer" tone="warning" />
                                    @endcan

                                    @can('convertToInvoice', $deliveryNote)
                                        @if(auth()->user()?->hasPermission('invoices.create'))
                                            <form method="POST" action="{{ route('invoices.store') }}" class="inline-flex">
                                                @csrf
                                                <input type="hidden" name="source_type" value="delivery_note">
                                                <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                                                <x-action-button type="submit" icon="receipt" label="Creer la facture" tone="success" />
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun BL trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $deliveryNotes->links() }}
        </div>
    </div>
@endsection
