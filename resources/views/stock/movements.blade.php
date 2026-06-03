@extends('layouts.app')

@section('title', 'Mouvements stock | SFMID Gestion')
@section('subtitle', 'Gestion du stock')
@section('page-title', 'Historique mouvements')

@section('content')
    @include('stock._nav')

    <div class="mb-6 flex flex-wrap gap-3">
        <x-button :href="route('stock.entries.create')" icon="package-plus">Entree fournisseur</x-button>
        <x-button :href="route('stock.exits.create')" tone="danger" icon="package-minus">Sortie manuelle</x-button>
        <x-button :href="route('stock.adjustments.create')" tone="secondary" icon="sliders-horizontal">Ajustement</x-button>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-3">
            <select name="status" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"><option value="">Tous statuts</option>@foreach($statuses as $status)<option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>{{ $status['label'] }}</option>@endforeach</select>
            <select name="type" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"><option value="">Tous types</option>@foreach($types as $type)<option value="{{ $type['value'] }}" @selected($filters['type'] === $type['value'])>{{ $type['label'] }}</option>@endforeach</select>
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left">Date</th><th class="px-5 py-4 text-left">Produit</th><th class="px-5 py-4 text-left">Type</th><th class="px-5 py-4 text-right">Quantite</th><th class="px-5 py-4 text-left">Statut</th><th class="px-5 py-4 text-left">Motif</th><th class="px-5 py-4 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $movement)
                        <tr>
                            <td class="px-5 py-4">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4"><p class="font-semibold">{{ $movement->product?->name }}</p><p class="text-xs text-slate-500">{{ $movement->product?->code }}</p></td>
                            <td class="px-5 py-4">{{ $movement->type->label() }}</td>
                            <td class="px-5 py-4 text-right font-semibold">{{ \App\Support\NumberFormatter::quantity($movement->quantity) }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $movement->status->badgeClasses() }}">{{ $movement->status->label() }}</span></td>
                            <td class="px-5 py-4">{{ str($movement->reason)->limit(60) }}</td>
                            <td class="px-5 py-4 text-right">
                                @can('validate', $movement)
                                    <form method="POST" action="{{ route('stock.movements.validate', $movement) }}" class="inline-flex">@csrf<x-action-button type="submit" icon="check" label="Valider le mouvement" tone="success" /></form>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucun mouvement trouve.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-5 py-4">{{ $movements->links() }}</div>
    </div>
@endsection
