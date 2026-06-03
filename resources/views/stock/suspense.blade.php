@extends('layouts.app')

@section('title', 'Stock en suspens | SFMID Gestion')
@section('subtitle', 'Gestion du stock')
@section('page-title', 'Stock en suspens')

@section('content')
    @include('stock._nav')

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Stock en suspens par client</h3>
            <p class="mt-1 text-sm text-slate-500">Produits livrés, facturés ou non, en attente de paiement complet.</p>
        </div>
        <x-button :href="route('stock.reports.pdf', ['report' => 'suspense'])" target="_blank" tone="secondary" icon="printer">PDF rapport</x-button>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <select name="client_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" onchange="this.form.submit()">
            <option value="">Tous les clients</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected($filters['client_id'] === $client->id)>{{ $client->code }} - {{ $client->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left">Client</th>
                    <th class="px-5 py-4 text-left">Produit</th>
                    <th class="px-5 py-4 text-left">BL</th>
                    <th class="px-5 py-4 text-left">Facture</th>
                    <th class="px-5 py-4 text-right">Quantité</th>
                    <th class="px-5 py-4 text-right">Restant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($suspenses as $suspense)
                    <tr>
                        <td class="px-5 py-4">{{ $suspense->client?->name }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ $suspense->product?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $suspense->product?->code }}</p>
                        </td>
                        <td class="px-5 py-4">{{ $suspense->deliveryNote?->number }}</td>
                        <td class="px-5 py-4">{{ $suspense->invoice?->number ?: 'Non facturé' }}</td>
                        <td class="px-5 py-4 text-right font-bold">{{ \App\Support\NumberFormatter::quantity($suspense->quantity) }}</td>
                        <td class="px-5 py-4 text-right font-bold">{{ \App\Support\NumberFormatter::quantity($suspense->remainingQuantity()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun stock en suspens ouvert.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-4">{{ $suspenses->links() }}</div>
    </div>
@endsection
