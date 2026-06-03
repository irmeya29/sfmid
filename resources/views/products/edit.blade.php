@extends('layouts.app')

@section('title', 'Modifier produit | SFMID Gestion')
@section('subtitle', 'Catalogue et stock')
@section('page-title', 'Modifier produit')

@section('content')
    <div class="max-w-6xl">
        <div class="mb-6">
            <a href="{{ route('products.show', $product) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour à la fiche produit
            </a>
        </div>

        @include('products._form', [
            'product' => $product,
            'categories' => $categories,
            'statuses' => $statuses,
            'stockKinds' => $stockKinds,
            'action' => route('products.update', $product),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
        ])
    </div>
@endsection