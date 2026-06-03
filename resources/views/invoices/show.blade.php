@extends('layouts.app')

@section('title', $invoice->number.' | SFMID Gestion')
@section('subtitle', 'Détail facture')
@section('page-title', $invoice->number)

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('invoices.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">← Retour aux factures</a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $invoice->status->badgeClasses() }}">{{ $invoice->status->label() }}</span>
                <span class="text-sm text-slate-500">Date : {{ $invoice->issue_date?->format('d/m/Y') }}</span>
                <span class="text-sm font-semibold text-slate-700">Solde : {{ number_format((float) $invoice->balance_due, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('exportPdf', $invoice)<x-button :href="route('invoices.pdf', $invoice)" target="_blank" tone="secondary" icon="printer">PDF / Imprimer</x-button>@endcan
            @can('update', $invoice)<x-button :href="route('invoices.edit', $invoice)" icon="pencil">Modifier</x-button>@endcan
            @can('submit', $invoice)<form method="POST" action="{{ route('invoices.submit', $invoice) }}">@csrf<x-button type="submit" tone="secondary" icon="send">Soumettre</x-button></form>@endcan
            @can('validate', $invoice)<form method="POST" action="{{ route('invoices.validate', $invoice) }}">@csrf<x-button type="submit" tone="success" icon="check">Valider</x-button></form>@endcan
            @can('pay', $invoice)<x-button :href="route('payments.create', ['invoice_id' => $invoice->id])" tone="success" icon="banknote">Encaisser</x-button>@endcan
        </div>
    </div>

    @if($invoice->rejection_reason)
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p class="font-bold">Motif de rejet</p><p class="mt-2">{{ $invoice->rejection_reason }}</p>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-base font-bold text-slate-950">Client</h3>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nom</p><p class="mt-1 font-semibold">{{ $invoice->client?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Code</p><p class="mt-1 font-semibold">{{ $invoice->client?->code }}</p></div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Source</p>
                    <p class="mt-1 font-semibold">
                        @if($invoice->deliveryNote)
                            BL {{ $invoice->deliveryNote->number }}
                        @elseif($invoice->customerOrder)
                            BC {{ $invoice->customerOrder->customer_reference ?: $invoice->customerOrder->number }}
                        @elseif($invoice->proforma)
                            Proforma {{ $invoice->proforma->number }}
                        @else
                            Facture directe
                        @endif
                    </p>
                </div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Échéance</p><p class="mt-1 font-semibold">{{ $invoice->due_date?->format('d/m/Y') ?: 'Non renseignée' }}</p></div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-950">Résumé</h3>
            <div class="mt-5 space-y-4">
                <div class="flex justify-between"><span class="text-sm text-slate-500">Total</span><span class="font-bold">{{ number_format((float) $invoice->total, 0, ',', ' ') }} FCFA</span></div>
                <div class="flex justify-between"><span class="text-sm text-slate-500">Payé</span><span class="font-bold">{{ number_format((float) $invoice->paid_amount, 0, ',', ' ') }} FCFA</span></div>
                <div class="border-t pt-4 flex justify-between"><span class="font-bold">Reste à payer</span><span class="text-xl font-black">{{ number_format((float) $invoice->balance_due, 0, ',', ' ') }} FCFA</span></div>
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b px-6 py-4"><h3 class="text-base font-bold">Lignes facturées</h3></div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left">Produit</th><th class="px-5 py-4 text-right">Qté</th><th class="px-5 py-4 text-right">Prix</th><th class="px-5 py-4 text-right">Remise</th><th class="px-5 py-4 text-right">Total</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($invoice->items as $item)
                    <tr><td class="px-5 py-4"><p class="font-semibold">{{ $item->product_name }}</p><p class="text-xs text-slate-500">{{ $item->product_code }} · {{ $item->unit }}</p></td><td class="px-5 py-4 text-right">{{ \App\Support\NumberFormatter::quantity($item->quantity) }}</td><td class="px-5 py-4 text-right">{{ number_format((float) $item->unit_price, 0, ',', ' ') }}</td><td class="px-5 py-4 text-right">{{ number_format((float) $item->discount_amount, 0, ',', ' ') }}</td><td class="px-5 py-4 text-right font-bold">{{ number_format((float) $item->line_total, 0, ',', ' ') }} FCFA</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-950">Paiements liés</h3>
        <div class="mt-4 space-y-3">
            @forelse($invoice->payments as $payment)
                <div class="flex flex-col gap-2 rounded-xl bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><a href="{{ route('payments.show', $payment) }}" class="font-semibold text-slate-900">{{ $payment->number }}</a><p class="text-xs text-slate-500">{{ $payment->payment_date?->format('d/m/Y') }} · {{ $payment->method }}</p></div>
                    <div class="text-right"><p class="font-bold">{{ number_format((float) $payment->amount, 0, ',', ' ') }} FCFA</p><span class="rounded-full px-3 py-1 text-xs font-bold {{ $payment->status->badgeClasses() }}">{{ $payment->status->label() }}</span></div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun paiement enregistré.</p>
            @endforelse
        </div>
    </div>

    @can('reject', $invoice)
        <div class="mt-6 rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-red-700">Rejeter la facture</h3>
            <form method="POST" action="{{ route('invoices.reject', $invoice) }}" class="mt-4">@csrf
                <textarea name="reason" required rows="3" class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm">{{ old('reason') }}</textarea>
                <x-button type="submit" tone="danger" icon="x" class="mt-3">Rejeter</x-button>
            </form>
        </div>
    @endcan
@endsection
