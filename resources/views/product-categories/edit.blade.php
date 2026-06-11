@extends('layouts.app')

@section('title', 'Modifier categorie produit | SFMID Gestion')
@section('subtitle', 'Catalogue produits')
@section('page-title', 'Modifier categorie produit')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('product-categories.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                &larr; Retour aux categories
            </a>
        </div>

        @include('product-categories._form', [
            'category' => $category,
            'parents' => $parents,
            'action' => route('product-categories.update', $category),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
        ])
    </div>
@endsection
