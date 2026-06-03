@extends('layouts.app')

@section('title', 'Modifier proforma | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Modifier proforma')

@section('content')
    <div class="max-w-7xl">
        <div class="mb-6">
            <a href="{{ route('proformas.show', $proforma) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour à la proforma
            </a>
        </div>

        @include('proformas._form', [
            'proforma' => $proforma,
            'clients' => $clients,
            'products' => $products,
            'lineItems' => $lineItems,
            'action' => route('proformas.update', $proforma),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
        ])
    </div>
@endsection