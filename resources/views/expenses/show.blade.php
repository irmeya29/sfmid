@extends('layouts.app')

@section('title', $expense->number.' | SFMID Gestion')
@section('subtitle', 'Detail depense')
@section('page-title', $expense->number)

@section('content')
    @php
        $attachmentUrl = $expense->attachment_path ? route('expenses.attachment', $expense) : null;
        $attachmentExtension = $expense->attachment_path ? strtolower(pathinfo($expense->attachment_path, PATHINFO_EXTENSION)) : null;
        $isPreviewableImage = in_array($attachmentExtension, ['jpg', 'jpeg', 'png', 'webp', 'bmp'], true);
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600">Retour aux depenses</a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $expense->status->badgeClasses() }}">{{ $expense->status->label() }}</span>
                @if($expense->category?->is_sensitive)
                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Charge sensible</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $expense)
                <x-button :href="route('expenses.edit', $expense)" icon="pencil">Modifier</x-button>
            @endcan
            @can('submit', $expense)
                <form method="POST" action="{{ route('expenses.submit', $expense) }}">@csrf<x-button type="submit" tone="secondary" icon="send">Soumettre</x-button></form>
            @endcan
            @can('validate', $expense)
                <form method="POST" action="{{ route('expenses.validate', $expense) }}">@csrf<x-button type="submit" tone="success" icon="check">Valider</x-button></form>
            @endcan
            @can('delete', $expense)
                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm="Supprimer cette depense brouillon ?">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" tone="danger" icon="trash-2">Supprimer</x-button>
                </form>
            @endcan
        </div>
    </div>

    @if($expense->rejection_reason)
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p class="font-bold">Motif de rejet</p>
            <p class="mt-2">{{ $expense->rejection_reason }}</p>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-base font-bold">Informations</h3>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Categorie</p><p class="font-semibold">{{ $expense->category?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="font-semibold">{{ $expense->expense_date?->format('d/m/Y') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Beneficiaire</p><p class="font-semibold">{{ $expense->beneficiary ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Paiement</p><p class="font-semibold">{{ $expense->payment_method ?: '-' }} {{ $expense->payment_reference ? '- '.$expense->payment_reference : '' }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-slate-500">Description</p><p class="mt-1">{{ $expense->description }}</p></div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold">Montant</h3>
            <p class="mt-5 text-3xl font-black">{{ number_format((float) $expense->amount, 0, ',', ' ') }} FCFA</p>
            <p class="mt-3 text-sm text-slate-500">Creee par {{ $expense->creator?->name ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-base font-bold">Justificatif</h3>
            @if($attachmentUrl)
                <a href="{{ $attachmentUrl }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="external-link" class="h-4 w-4"></i>
                    Ouvrir
                </a>
            @endif
        </div>

        @if($attachmentUrl)
            @if($isPreviewableImage)
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                    <img src="{{ $attachmentUrl }}" alt="Justificatif {{ $expense->number }}" class="max-h-[32rem] w-full object-contain">
                </div>
            @else
                <div class="mt-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-[#2676B3]">
                        <i data-lucide="file-text" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">Document justificatif</p>
                        <p class="text-sm text-slate-500">{{ strtoupper($attachmentExtension ?: 'FICHIER') }}</p>
                    </div>
                </div>
            @endif
        @else
            <p class="mt-4 text-sm text-slate-500">Aucun justificatif joint.</p>
        @endif
    </div>

    @can('reject', $expense)
        <div class="mt-6 rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-red-700">Rejeter la depense</h3>
            <form method="POST" action="{{ route('expenses.reject', $expense) }}" class="mt-4">
                @csrf
                <textarea name="reason" required rows="3" class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm">{{ old('reason') }}</textarea>
                <x-button type="submit" tone="danger" icon="x" class="mt-3">Rejeter</x-button>
            </form>
        </div>
    @endcan

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold">Historique de validation</h3>
        <div class="mt-5 space-y-3">
            @forelse($expense->validationHistories as $history)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="font-semibold">{{ $history->action->label() }}</p>
                        <p class="text-xs text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }} - {{ $history->user?->name ?? 'Systeme' }}</p>
                    </div>
                    @if($history->reason)<p class="mt-2 text-sm text-red-700">{{ $history->reason }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun historique.</p>
            @endforelse
        </div>
    </div>
@endsection
