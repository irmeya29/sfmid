@extends('layouts.app')

@section('title', 'Nouveau produit | SFMID Gestion')
@section('subtitle', 'Catalogue et stock')
@section('page-title', 'Nouveau produit')

@section('content')
    <div class="max-w-6xl">
        <div class="mb-6">
            <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour aux produits
            </a>
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