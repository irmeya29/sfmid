@extends('layouts.app')

@section('title', 'Bons de commande clients | SFMID Gestion')
@section('subtitle', 'Facturation')
@section('page-title', 'Bons de commande clients')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="search" value="{{ request('search') }}" placeholder="Numero, reference client, client..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <x-button type="submit" tone="secondary" icon="search">Filtrer</x-button>
        </form>
        <x-button :href="route('customer-orders.create')" icon="plus">Nouveau BC client</x-button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[920px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Numero</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Client</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Reference client</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Date</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Montant</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-slate-950">{{ $order->number }}</td>
                            <td class="px-5 py-4">{{ $order->client?->name }}</td>
                            <td class="px-5 py-4">{{ $order->customer_reference ?: '-' }}</td>
                            <td class="px-5 py-4">{{ $order->order_date?->format('d/m/Y') }}</td>
                            <td class="px-5 py-4 text-right font-semibold">{{ number_format((float) $order->total, 0, ',', ' ') }} FCFA</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <x-action-button :href="route('customer-orders.show', $order)" icon="eye" label="Voir" />
                                    <form method="POST" action="{{ route('customer-orders.convert-to-delivery-note', $order) }}" class="inline-flex">
                                        @csrf
                                        <x-action-button type="submit" icon="truck" label="Creer un BL" tone="success" />
                                    </form>
                                    <form method="POST" action="{{ route('customer-orders.convert-to-invoice', $order) }}" class="inline-flex">
                                        @csrf
                                        <x-action-button type="submit" icon="file-badge" label="Creer une facture" tone="secondary" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun bon de commande client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $orders->links() }}</div>
@endsection
