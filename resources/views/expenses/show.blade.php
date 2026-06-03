@extends('layouts.app')

@section('title', $expense->number.' | SFMID Gestion')
@section('subtitle', 'Détail dépense')
@section('page-title', $expense->number)

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600">← Retour aux dépenses</a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $expense->status->badgeClasses() }}">{{ $expense->status->label() }}</span>
                @if($expense->category?->is_sensitive)<span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Charge sensible</span>@endif
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $expense)<x-button :href="route('expenses.edit', $expense)" icon="pencil">Modifier</x-button>@endcan
            @can('submit', $expense)<form method="POST" action="{{ route('expenses.submit', $expense) }}">@csrf<x-button type="submit" tone="secondary" icon="send">Soumettre</x-button></form>@endcan
            @can('validate', $expense)<form method="POST" action="{{ route('expenses.validate', $expense) }}">@csrf<x-button type="submit" tone="success" icon="check">Valider</x-button></form>@endcan
        </div>
    </div>

    @if($expense->rejection_reason)
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"><p class="font-bold">Motif de rejet</p><p class="mt-2">{{ $expense->rejection_reason }}</p></div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-base font-bold">Informations</h3>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Catégorie</p><p class="font-semibold">{{ $expense->category?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="font-semibold">{{ $expense->expense_date?->format('d/m/Y') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Bénéficiaire</p><p class="font-semibold">{{ $expense->beneficiary ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Paiement</p><p class="font-semibold">{{ $expense->payment_method ?: '-' }} {{ $expense->payment_reference ? '· '.$expense->payment_reference : '' }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-slate-500">Description</p><p class="mt-1">{{ $expense->description }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-slate-500">Justificatif</p><p class="font-semibold">@if($expense->attachment_path)<a href="{{ asset('storage/'.$expense->attachment_path) }}" target="_blank">Ouvrir le justificatif</a>@else Aucun @endif</p></div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold">Montant</h3>
            <p class="mt-5 text-3xl font-black">{{ number_format((float) $expense->amount, 0, ',', ' ') }} FCFA</p>
            <p class="mt-3 text-sm text-slate-500">Créée par {{ $expense->creator?->name ?? 'N/A' }}</p>
        </div>
    </div>

    @can('reject', $expense)
        <div class="mt-6 rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-red-700">Rejeter la dépense</h3>
            <form method="POST" action="{{ route('expenses.reject', $expense) }}" class="mt-4">@csrf
                <textarea name="reason" required rows="3" class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm">{{ old('reason') }}</textarea>
                <x-button type="submit" tone="danger" icon="x" class="mt-3">Rejeter</x-button>
            </form>
        </div>
    @endcan

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold">Historique de validation</h3>
        <div class="mt-5 space-y-3">
            @forelse($expense->validationHistories as $history)
                <div class="rounded-xl bg-slate-50 px-4 py-3"><div class="flex justify-between"><p class="font-semibold">{{ $history->action->label() }}</p><p class="text-xs text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->user?->name ?? 'Système' }}</p></div>@if($history->reason)<p class="mt-2 text-sm text-red-700">{{ $history->reason }}</p>@endif</div>
            @empty
                <p class="text-sm text-slate-500">Aucun historique.</p>
            @endforelse
        </div>
    </div>
@endsection
