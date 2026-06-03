@extends('layouts.app')

@section('title', 'Rapports | SFMID Gestion')
@section('subtitle', 'Statistiques')
@section('page-title', 'Rapports et statistiques')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Synthese operationnelle</h3>
            <p class="mt-1 text-sm text-slate-500">Ventes, impayes, encaissements, stock et depenses.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('reports.pdf', request()->query()) }}" target="_blank" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">PDF</a>
            <a href="{{ route('reports.unpaid-invoices.pdf', request()->query()) }}" target="_blank" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">PDF impayees</a>
            <a href="{{ route('reports.excel', request()->query()) }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Excel</a>
        </div>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-6">
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <select name="client_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Tous clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected($filters['client_id'] === $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <select name="expense_category_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Categories charges</option>
                @foreach($expenseCategories as $category)
                    <option value="{{ $category->id }}" @selected($filters['expense_category_id'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="product_category_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Categories produits</option>
                @foreach($productCategories as $category)
                    <option value="{{ $category->id }}" @selected($filters['product_category_id'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    @include('reports._content')
@endsection
