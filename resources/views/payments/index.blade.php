@extends('layouts.app')

@section('title', 'Paiements | SFMID Gestion')
@section('subtitle', 'Caisse')
@section('page-title', 'Paiements')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Liste des paiements</h3>
            <p class="mt-1 text-sm text-slate-500">Encaissements, validation et reçus.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <x-button :href="route('payments.cash-journal')" tone="secondary" icon="book-open">Journal caisse</x-button>
            @can('create', \App\Models\Payment::class)
                <x-button :href="route('payments.create')" icon="plus">Nouveau paiement</x-button>
            @endcan
        </div>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-4">
            <input name="search" value="{{ $filters['search'] }}" placeholder="Reçu, référence ou facture..." class="rounded-xl border border-slate-300 px-4 py-3 text-sm lg:col-span-2">
            <select name="status" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Tous statuts</option>
                @foreach($statuses as $status)
                    <option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>{{ $status['label'] }}</option>
                @endforeach
            </select>
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left">Reçu</th>
                    <th class="px-5 py-4 text-left">Facture</th>
                    <th class="px-5 py-4 text-left">Client</th>
                    <th class="px-5 py-4 text-right">Montant</th>
                    <th class="px-5 py-4 text-left">Statut</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr>
                        <td class="px-5 py-4 font-semibold">
                            {{ $payment->number }}
                            <p class="text-xs text-slate-500">{{ $payment->payment_date?->format('d/m/Y') }} · {{ $payment->method }}</p>
                        </td>
                        <td class="px-5 py-4">{{ $payment->invoice?->number }}</td>
                        <td class="px-5 py-4">{{ $payment->invoice?->client?->name }}</td>
                        <td class="px-5 py-4 text-right font-bold">{{ number_format((float) $payment->amount, 0, ',', ' ') }} FCFA</td>
                        <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $payment->status->badgeClasses() }}">{{ $payment->status->label() }}</span></td>
                        <td class="px-5 py-4 text-right">
                            <x-action-button :href="route('payments.show', $payment)" icon="eye" label="Voir le paiement" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun paiement trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-4">{{ $payments->links() }}</div>
    </div>
@endsection
