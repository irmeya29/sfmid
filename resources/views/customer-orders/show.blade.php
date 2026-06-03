@extends('layouts.app')

@section('title', $customerOrder->number.' | SFMID Gestion')
@section('subtitle', 'Bon de commande client')
@section('page-title', $customerOrder->number)

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('customer-orders.index') }}" class="text-sm font-semibold text-slate-600 hover:text-[#2676B3]">Retour aux BC clients</a>
            <p class="mt-2 text-sm text-slate-500">Reference client : <span class="font-semibold text-slate-950">{{ $customerOrder->customer_reference ?: '-' }}</span></p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('customer-orders.convert-to-delivery-note', $customerOrder) }}" class="inline-flex">
                @csrf
                <x-button type="submit" icon="truck" tone="success">Creer BL</x-button>
            </form>
            <form method="POST" action="{{ route('customer-orders.convert-to-invoice', $customerOrder) }}" class="inline-flex">
                @csrf
                <x-button type="submit" icon="file-badge" tone="secondary">Creer facture</x-button>
            </form>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <h3 class="text-base font-semibold text-slate-950">Client</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs uppercase text-slate-500">Nom</p><p class="font-semibold">{{ $customerOrder->client?->name }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">Code</p><p class="font-semibold">{{ $customerOrder->client?->code }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">Date BC</p><p class="font-semibold">{{ $customerOrder->order_date?->format('d/m/Y') }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">Source</p><p class="font-semibold">{{ $customerOrder->proforma?->number ?: 'Creation independante' }}</p></div>
            </div>
        </x-card>
        <x-card>
            <h3 class="text-base font-semibold text-slate-950">Montant</h3>
            <p class="mt-4 text-2xl font-semibold text-[#2676B3]">{{ number_format((float) $customerOrder->total, 0, ',', ' ') }} FCFA</p>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $customerOrder->confirmed_terms ?: 'Aucune condition confirmee.' }}</p>
        </x-card>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-base font-semibold text-slate-950">Articles commandes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[900px] text-sm">
                <thead><tr><th class="text-left">Reference</th><th class="text-left">Designation</th><th class="text-right">Qte</th><th class="text-right">PU</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    @foreach($customerOrder->items as $item)
                        <tr>
                            <td>{{ $item->client_product_reference ?: ($item->product_internal_reference ?: $item->product_code) }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td class="text-right">{{ \App\Support\NumberFormatter::quantity($item->quantity) }}</td>
                            <td class="text-right">{{ number_format((float) $item->unit_price, 0, ',', ' ') }}</td>
                            <td class="text-right font-semibold">{{ number_format((float) $item->line_total, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
