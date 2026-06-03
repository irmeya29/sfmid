@extends('layouts.app')

@section('title', 'Modifier BL | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Modifier BL')

@section('content')
    <div class="max-w-7xl">
        <div class="mb-6">
            <a href="{{ route('delivery-notes.show', $deliveryNote) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">← Retour au BL</a>
        </div>

        @include('delivery-notes._form', [
            'deliveryNote' => $deliveryNote,
            'clients' => $clients,
            'products' => $products,
            'lineItems' => $lineItems,
            'action' => route('delivery-notes.update', $deliveryNote),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
        ])
    </div>
@endsection
