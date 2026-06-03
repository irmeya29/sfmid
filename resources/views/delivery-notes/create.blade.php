@extends('layouts.app')

@section('title', 'Nouveau BL | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Nouveau BL')

@section('content')
    <div class="max-w-7xl">
        <div class="mb-6">
            <a href="{{ route('delivery-notes.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">← Retour aux BL</a>
        </div>

        @include('delivery-notes._form', [
            'deliveryNote' => $deliveryNote,
            'clients' => $clients,
            'products' => $products,
            'lineItems' => $lineItems,
            'action' => route('delivery-notes.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer le BL',
        ])
    </div>
@endsection
