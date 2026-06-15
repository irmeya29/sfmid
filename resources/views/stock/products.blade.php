@extends('layouts.app')

@section('title', $title.' | SFMID Gestion')
@section('subtitle', 'Gestion du stock')
@section('page-title', $title)

@section('content')
    @include('stock._nav')

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">{{ $title }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('stock.entries.create') }}" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white">Entrée</a>
            <a href="{{ route('stock.exits.create') }}" class="rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white">Sortie</a>
            <a href="{{ route('stock.transfers.create') }}" class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white">Transfert</a>
            <a href="{{ route('stock.adjustments.create') }}" class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white">Ajustement</a>
            <a href="{{ route('stock.reports.pdf', ['report' => $stockColumn === 'tool_stock' ? 'tool' : 'physical']) }}" target="_blank" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">PDF</a>
        </div>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-[1fr_260px_auto]">
            <input name="search" value="{{ $filters['search'] }}" placeholder="Code, nom, marque..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <select name="stock_site_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Tous les sites</option>
                @foreach($stockSites as $site)
                    <option value="{{ $site->id }}" @selected((string) $filters['stock_site_id'] === (string) $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left font-bold text-slate-600">Produit</th>
                    <th class="px-5 py-4 text-left font-bold text-slate-600">Catégorie</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Physique</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Réservé</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Suspens</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Outil</th>
                    <th class="px-5 py-4 text-right font-bold text-slate-600">Seuil</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    @php
                        $siteStock = $filters['stock_site_id'] ? $product->stockSiteStocks->first() : null;
                        $physicalStock = $siteStock ? $siteStock->physical_stock : $product->physical_stock;
                        $reservedStock = $siteStock ? $siteStock->reserved_stock : $product->reserved_stock;
                        $suspenseStock = $siteStock ? $siteStock->suspense_stock : $product->suspense_stock;
                        $toolStock = $siteStock ? $siteStock->tool_stock : $product->tool_stock;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4"><p class="font-semibold text-slate-950">{{ $product->name }}</p><p class="text-xs text-slate-500">{{ $product->code }} · {{ $product->unit }}</p></td>
                        <td class="px-5 py-4">{{ $product->category?->name }}</td>
                        <td class="px-5 py-4 text-right font-bold @if($stockColumn === 'physical_stock') text-slate-950 @endif">{{ \App\Support\NumberFormatter::quantity($physicalStock) }}</td>
                        <td class="px-5 py-4 text-right font-bold @if($stockColumn === 'reserved_stock') text-slate-950 @endif">{{ \App\Support\NumberFormatter::quantity($reservedStock) }}</td>
                        <td class="px-5 py-4 text-right font-bold @if($stockColumn === 'suspense_stock') text-slate-950 @endif">{{ \App\Support\NumberFormatter::quantity($suspenseStock) }}</td>
                        <td class="px-5 py-4 text-right font-bold @if($stockColumn === 'tool_stock') text-slate-950 @endif">{{ \App\Support\NumberFormatter::quantity($toolStock) }}</td>
                        <td class="px-5 py-4 text-right">{{ \App\Support\NumberFormatter::quantity($product->alert_threshold) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucun produit trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-200 px-5 py-4">{{ $products->links() }}</div>
    </div>
@endsection
