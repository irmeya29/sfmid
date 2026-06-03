@extends('layouts.app')

@section('title', 'Rapport stock bas | SFMID Gestion')
@section('subtitle', 'Gestion du stock')
@section('page-title', 'Rapport stock bas')

@section('content')
    @include('stock._nav')
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500">Produits dont le stock physique est inférieur ou égal au seuil d’alerte.</p>
        <a href="{{ route('stock.reports.pdf', ['report' => 'low_stock']) }}" target="_blank" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">PDF rapport</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left">Produit</th><th class="px-5 py-4 text-left">Catégorie</th><th class="px-5 py-4 text-right">Stock</th><th class="px-5 py-4 text-right">Seuil</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($products as $product)
                <tr><td class="px-5 py-4"><p class="font-semibold">{{ $product->name }}</p><p class="text-xs text-slate-500">{{ $product->code }}</p></td><td class="px-5 py-4">{{ $product->category?->name }}</td><td class="px-5 py-4 text-right font-bold text-red-700">{{ \App\Support\NumberFormatter::quantity($product->physical_stock) }}</td><td class="px-5 py-4 text-right">{{ \App\Support\NumberFormatter::quantity($product->alert_threshold) }}</td></tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Aucun stock bas.</td></tr>
            @endforelse
        </tbody></table>
        <div class="border-t px-5 py-4">{{ $products->links() }}</div>
    </div>
@endsection
