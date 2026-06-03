@extends('layouts.app')

@section('title', 'Journal caisse | SFMID Gestion')
@section('subtitle', 'Caisse')
@section('page-title', 'Journal des encaissements')

@section('content')
    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-3">
            <input type="date" name="date" value="{{ $date }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <a href="{{ route('payments.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700">Retour paiements</a>
        </div>
    </form>
    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-sm text-emerald-800">Total affiché</p><p class="text-2xl font-black text-emerald-950">{{ number_format((float) $total, 0, ',', ' ') }} FCFA</p></div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left">Date</th><th class="px-5 py-4 text-left">Reçu</th><th class="px-5 py-4 text-left">Client</th><th class="px-5 py-4 text-left">Mode</th><th class="px-5 py-4 text-right">Montant</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($payments as $payment)
                <tr><td class="px-5 py-4">{{ $payment->payment_date?->format('d/m/Y') }}</td><td class="px-5 py-4"><a href="{{ route('payments.show', $payment) }}" class="font-semibold">{{ $payment->number }}</a></td><td class="px-5 py-4">{{ $payment->invoice?->client?->name }}</td><td class="px-5 py-4">{{ $payment->method }}</td><td class="px-5 py-4 text-right font-bold">{{ number_format((float) $payment->amount, 0, ',', ' ') }} FCFA</td></tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucun encaissement validé.</td></tr>
            @endforelse
        </tbody></table>
        <div class="border-t px-5 py-4">{{ $payments->links() }}</div>
    </div>
@endsection
