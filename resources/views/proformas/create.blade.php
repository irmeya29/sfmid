@extends('layouts.app')

@section('title', 'Nouvelle proforma | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Nouvelle proforma')

@section('content')
    <div class="max-w-7xl">
        <div class="mb-6">
            <a href="{{ route('proformas.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour aux proformas
            </a>
        </div>

        @include('proformas._form', [
            'proforma' => $proforma,
            'clients' => $clients,
            'products' => $products,
            'lineItems' => $lineItems,
            'action' => route('proformas.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer la proforma',
        ])
    </div>
@endsection