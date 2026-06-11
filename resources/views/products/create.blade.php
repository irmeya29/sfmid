@extends('layouts.app')

@section('title', 'Nouveau produit | SFMID Gestion')
@section('subtitle', 'Catalogue et stock')
@section('page-title', 'Nouveau produit')

@section('content')
    <div class="max-w-6xl">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour aux produits
            </a>

            @can('import', \App\Models\Product::class)
                <x-button :href="route('products.import.create')" tone="secondary" icon="upload">Import CSV</x-button>
            @endcan
        </div>

        @include('products._form', [
            'product' => $product,
            'categories' => $categories,
            'statuses' => $statuses,
            'stockKinds' => $stockKinds,
            'action' => route('products.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer le produit',
        ])
    </div>
@endsection
