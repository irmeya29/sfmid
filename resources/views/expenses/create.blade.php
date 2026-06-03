@extends('layouts.app')

@section('title', 'Nouvelle dépense | SFMID Gestion')
@section('subtitle', 'Charges et dépenses')
@section('page-title', 'Nouvelle dépense')

@section('content')
    <div class="max-w-5xl">
        <div class="mb-6"><a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600">← Retour aux dépenses</a></div>
        @include('expenses._form', [
            'expense' => $expense,
            'categories' => $categories,
            'action' => route('expenses.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer la dépense',
        ])
    </div>
@endsection
