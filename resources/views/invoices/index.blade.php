@extends('layouts.app')

@section('title', 'Factures | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Factures')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Liste des factures</h3>
            <p class="mt-1 text-sm text-slate-500">Facturation depuis BL livré, suivi du solde et des paiements.</p>
        </div>
        @can('create', \App\Models\Invoice::class)
            <x-button :href="route('invoices.create')" icon="file-plus-2">Nouvelle facture</x-button>
        @endcan
    </div>

    <form method="GET" action="{{ route('invoices.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input name="search" value="{{ $filters['search'] }}" placeholder="Numéro facture ou client..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
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
        <div class="mt-4 flex gap-3">
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('invoices.index')" tone="secondary" icon="rotate-ccw">Réinitialiser</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left font-bold text-slate-600">Numéro</th>
                    <th class="px-5 py-4 text-left font-bold text-slate-600">Client</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Total</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Payé</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Solde</th>
                    <th class="px-5 py-4 text-left font-bold text-slate-600">Statut</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-950">{{ $invoice->number }}</p>
                            <p class="text-xs text-slate-500">
                                @if($invoice->deliveryNote)
                                    BL {{ $invoice->deliveryNote->number }}
                                @elseif($invoice->customerOrder)
                                    BC {{ $invoice->customerOrder->customer_reference ?: $invoice->customerOrder->number }}
                                @elseif($invoice->proforma)
                                    Proforma {{ $invoice->proforma->number }}
                                @else
                                    Facture directe
                                @endif
                            </p>
                        </td>
                        <td class="px-5 py-4"><p class="font-semibold text-slate-950">{{ $invoice->client?->name }}</p><p class="text-xs text-slate-500">{{ $invoice->client?->code }}</p></td>
                        <td class="px-5 py-4 text-right font-bold">{{ number_format((float) $invoice->total, 0, ',', ' ') }} FCFA</td>
                        <td class="px-5 py-4 text-right">{{ number_format((float) $invoice->paid_amount, 0, ',', ' ') }} FCFA</td>
                        <td class="px-5 py-4 text-right font-bold text-slate-950">{{ number_format((float) $invoice->balance_due, 0, ',', ' ') }} FCFA</td>
                        <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $invoice->status->badgeClasses() }}">{{ $invoice->status->label() }}</span></td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                <x-action-button :href="route('invoices.show', $invoice)" icon="eye" label="Voir la facture" />

                                @can('exportPdf', $invoice)
                                    <x-action-button :href="route('invoices.pdf', $invoice)" target="_blank" icon="printer" label="Imprimer / PDF" />
                                @endcan

                                @can('update', $invoice)
                                    <x-action-button :href="route('invoices.edit', $invoice)" icon="pencil" label="Modifier la facture" tone="info" />
                                @endcan

                                @can('submit', $invoice)
                                    <form method="POST" action="{{ route('invoices.submit', $invoice) }}" class="inline-flex">
                                        @csrf
                                        <x-action-button type="submit" icon="send" label="Soumettre en validation" tone="warning" />
                                    </form>
                                @endcan

                                @can('validate', $invoice)
                                    <form method="POST" action="{{ route('invoices.validate', $invoice) }}" class="inline-flex">
                                        @csrf
                                        <x-action-button type="submit" icon="check" label="Valider la facture" tone="success" />
                                    </form>
                                @endcan

                                @can('reject', $invoice)
                                    <x-action-button :href="route('invoices.show', $invoice)" icon="x" label="Rejeter avec motif" tone="danger" />
                                @endcan

                                @can('pay', $invoice)
                                    <x-action-button :href="route('payments.create', ['invoice_id' => $invoice->id])" icon="banknote" label="Enregistrer un paiement" tone="success" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucune facture trouvée.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-200 px-5 py-4">{{ $invoices->links() }}</div>
    </div>
@endsection
