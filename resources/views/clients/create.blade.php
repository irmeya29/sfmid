@extends('layouts.app')

@section('title', 'Nouveau client | SFMID Gestion')
@section('subtitle', 'Gestion commerciale')
@section('page-title', 'Nouveau client')

@section('content')
    <div class="max-w-5xl">
        <div class="mb-6">
            <a href="{{ route('clients.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour aux clients
            </a>
        </div>

        @include('clients._form', [
            'client' => $client,
            'action' => route('clients.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer le client',
        ])
    </div>
@endsection