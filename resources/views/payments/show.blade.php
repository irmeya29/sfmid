@extends('layouts.app')

@section('title', $payment->number.' | SFMID Gestion')
@section('subtitle', 'Détail paiement')
@section('page-title', $payment->number)

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div><a href="{{ route('payments.index') }}" class="text-sm font-semibold text-slate-600">← Retour aux paiements</a><div class="mt-3"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $payment->status->badgeClasses() }}">{{ $payment->status->label() }}</span></div></div>
        <div class="flex flex-wrap gap-3">
            @can('exportReceiptPdf', $payment)<a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">Reçu PDF</a>@endcan
            @can('submit', $payment)<form method="POST" action="{{ route('payments.submit', $payment) }}">@csrf<x-button type="submit" tone="secondary" icon="send">Soumettre</x-button></form>@endcan
            @can('validate', $payment)<form method="POST" action="{{ route('payments.validate', $payment) }}">@csrf<x-button type="submit" tone="success" icon="check">Valider</x-button></form>@endcan
        </div>
    </div>
    @if($payment->rejection_reason)<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"><p class="font-bold">Motif de rejet</p><p>{{ $payment->rejection_reason }}</p></div>@endif
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-base font-bold">Paiement</h3>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Facture</p><a href="{{ route('invoices.show', $payment->invoice) }}" class="font-semibold">{{ $payment->invoice?->number }}</a></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Client</p><p class="font-semibold">{{ $payment->invoice?->client?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="font-semibold">{{ $payment->payment_date?->format('d/m/Y') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Mode</p><p class="font-semibold">{{ $payment->method }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Référence</p><p class="font-semibold">{{ $payment->reference ?: 'Non renseignée' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Justificatif</p><p class="font-semibold">@if($payment->attachment_path)<a href="{{ asset('storage/'.$payment->attachment_path) }}" target="_blank">Ouvrir</a>@else Aucun @endif</p></div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold">Montants</h3>
            <div class="mt-5 space-y-4"><div class="flex justify-between"><span>Montant</span><span class="font-bold">{{ number_format((float) $payment->amount, 0, ',', ' ') }} FCFA</span></div><div class="flex justify-between"><span>Solde facture</span><span class="font-bold">{{ number_format((float) $payment->invoice?->balance_due, 0, ',', ' ') }} FCFA</span></div></div>
        </div>
    </div>
    @can('reject', $payment)
        <div class="mt-6 rounded-2xl border border-red-200 bg-white p-6 shadow-sm"><h3 class="text-base font-bold text-red-700">Rejeter le paiement</h3><form method="POST" action="{{ route('payments.reject', $payment) }}" class="mt-4">@csrf<textarea name="reason" required rows="3" class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm">{{ old('reason') }}</textarea><x-button type="submit" tone="danger" icon="x" class="mt-3">Rejeter</x-button></form></div>
    @endcan
@endsection
