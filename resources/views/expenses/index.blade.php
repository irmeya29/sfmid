@extends('layouts.app')

@section('title', 'Depenses | SFMID Gestion')
@section('subtitle', 'Charges et depenses')
@section('page-title', 'Depenses')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Liste des depenses</h3>
            <p class="mt-1 text-sm text-slate-500">Suivi des charges, justificatifs et validation interne.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('export', \App\Models\Expense::class)
                <x-button :href="route('expenses.pdf', request()->query())" target="_blank" tone="secondary" icon="file-down">PDF</x-button>
            @endcan
            @if(auth()->user()?->hasPermission('treasury.create_expense'))
                <x-button :href="route('expenses.create')" icon="receipt-text">Nouvelle depense</x-button>
            @endif
        </div>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-5">
            <select name="category_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Toutes categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Tous statuts</option>
                @foreach($statuses as $status)
                    <option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>{{ $status['label'] }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Numero</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Categorie</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Date</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Beneficiaire</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Montant</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Statut</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $expense)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $expense->number }}</p>
                                <p class="text-xs text-slate-500">{{ str($expense->description)->limit(70) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                {{ $expense->category?->name }}
                                @if($expense->category?->is_sensitive)
                                    <x-badge tone="red" class="ml-2">Sensible</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-4">{{ $expense->expense_date?->format('d/m/Y') }}</td>
                            <td class="px-5 py-4">{{ $expense->beneficiary ?: '-' }}</td>
                            <td class="px-5 py-4 text-right font-semibold">{{ number_format((float) $expense->amount, 0, ',', ' ') }} FCFA</td>
                            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $expense->status->badgeClasses() }}">{{ $expense->status->label() }}</span></td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <x-action-button :href="route('expenses.show', $expense)" icon="eye" label="Voir la depense" />
                                    @can('update', $expense)
                                        <x-action-button :href="route('expenses.edit', $expense)" icon="pencil" label="Modifier la depense" tone="info" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucune depense trouvee.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-5 py-4">{{ $expenses->links() }}</div>
    </div>
@endsection
