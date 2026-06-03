@extends('layouts.app')

@section('title', 'Modifier client | SFMID Gestion')
@section('subtitle', 'Gestion commerciale')
@section('page-title', 'Modifier client')

@section('content')
    <div class="max-w-5xl">
        <div class="mb-6">
            <a href="{{ route('clients.show', $client) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour à la fiche client
            </a>
        </div>

        @include('clients._form', [
            'client' => $client,
            'action' => route('clients.update', $client),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
        ])
    </div>
@endsection