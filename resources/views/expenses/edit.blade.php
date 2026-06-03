@extends('layouts.app')

@section('title', 'Modifier dépense | SFMID Gestion')
@section('subtitle', 'Charges et dépenses')
@section('page-title', 'Modifier dépense')

@section('content')
    <div class="max-w-5xl">
        <div class="mb-6"><a href="{{ route('expenses.show', $expense) }}" class="text-sm font-semibold text-slate-600">← Retour à la dépense</a></div>
        @include('expenses._form', [
            'expense' => $expense,
            'categories' => $categories,
            'action' => route('expenses.update', $expense),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer',
        ])
    </div>
@endsection
