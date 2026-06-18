@extends('layouts.app')

@section('title', 'Tresorerie | SFMID Gestion')
@section('subtitle', 'Flux financiers')
@section('page-title', 'Tresorerie')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Journal de tresorerie</h3>
            <p class="mt-1 text-sm text-slate-500">Les recettes proviennent automatiquement des paiements clients valides.</p>
        </div>
        @if(auth()->user()?->hasPermission('treasury.create_expense'))
            <x-button :href="route('treasury.index', array_merge(request()->query(), ['action' => 'create_expense'])).'#expense-form'" icon="receipt-text">Enregistrer une depense</x-button>
        @endif
    </div>

    <div class="grid gap-5 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Recettes automatiques</p>
            <p class="mt-2 text-2xl font-semibold text-green-700">{{ number_format($totalIn, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Depenses courantes</p>
            <p class="mt-2 text-2xl font-semibold text-red-700">{{ number_format($totalOut, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Solde periode</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($totalIn - $totalOut, 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    <form method="GET" class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-4">
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <select name="category_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Toutes charges</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    @if($showExpenseForm)
        <section id="expense-form" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Enregistrer une depense</h3>
                    <p class="mt-1 text-sm text-slate-500">Cette transaction sera enregistree comme sortie de tresorerie.</p>
                </div>
                <x-button :href="route('treasury.index', request()->except('action'))" tone="secondary" icon="x">Fermer</x-button>
            </div>

            <form method="POST" action="{{ route('treasury.expenses.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 lg:grid-cols-3">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Categorie</label>
                    <select name="expense_category_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <option value="">Selectionner</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('expense_category_id') === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('expense_category_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Montant</label>
                    <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('expense_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Mode paiement</label>
                    <select name="payment_method" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @foreach($paymentModes as $mode)
                            <option value="{{ $mode->code }}" @selected(old('payment_method') === $mode->code)>{{ $mode->name }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Beneficiaire</label>
                    <input name="beneficiary" value="{{ old('beneficiary') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('beneficiary')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Justificatif</label>
                    <input type="file" name="attachment" accept="application/pdf,image/*,.tif,.tiff,.bmp" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700">
                    @error('attachment')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="3" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end">
                    <x-button type="submit" icon="save" class="w-full">Enregistrer</x-button>
                </div>
            </form>
        </section>
    @endif

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left">Date</th>
                        <th class="px-5 py-4 text-left">Type</th>
                        <th class="px-5 py-4 text-left">Libelle</th>
                        <th class="px-5 py-4 text-left">Tiers</th>
                        <th class="px-5 py-4 text-right">Entree</th>
                        <th class="px-5 py-4 text-right">Sortie</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr>
                            <td class="px-5 py-4">{{ $entry['date']?->format('d/m/Y') }}</td>
                            <td class="px-5 py-4"><x-badge :tone="$entry['type'] === 'recette' ? 'green' : 'red'">{{ $entry['type'] }}</x-badge></td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $entry['label'] }}</p>
                                <p class="text-xs text-slate-500">{{ $entry['category'] }} - {{ $entry['method'] }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $entry['third_party'] ?: '-' }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-green-700">{{ $entry['amount_in'] ? number_format($entry['amount_in'], 0, ',', ' ') : '-' }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-red-700">{{ $entry['amount_out'] ? number_format($entry['amount_out'], 0, ',', ' ') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun mouvement de tresorerie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
