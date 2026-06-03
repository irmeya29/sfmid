@extends('layouts.app')

@section('title', 'Nouvelle catégorie produit | SFMID Gestion')
@section('subtitle', 'Catalogue produits')
@section('page-title', 'Nouvelle catégorie produit')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('product-categories.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour aux catégories
            </a>
        </div>

        @include('product-categories._form', [
            'category' => $category,
            'parents' => $parents,
            'action' => route('product-categories.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer la catégorie',
        ])
    </div>
@endsection