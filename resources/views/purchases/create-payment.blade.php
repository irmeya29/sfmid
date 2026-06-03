@extends('layouts.app')
@section('title','Règlement fournisseur | SFMID')
@section('subtitle','Achats')
@section('page-title','Règlement fournisseur')
@section('content')
<form method="POST" action="{{ route('purchases.payments.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
<div class="grid gap-4 lg:grid-cols-3"><select name="supplier_invoice_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" required><option value="">Facture fournisseur</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}" @selected($selectedInvoiceId===$invoice->id)>{{ $invoice->number }} - {{ $invoice->supplier?->name }} - Solde {{ number_format((float)$invoice->balance_due,0,',',' ') }}</option>@endforeach</select><input type="number" step="0.01" name="amount" placeholder="Montant" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" required><input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"><input name="method" value="cash" placeholder="Mode" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"><input name="reference" placeholder="Reference" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"></div><textarea name="notes" rows="3" placeholder="Notes" class="mt-4 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></textarea><div class="mt-4 flex justify-end"><x-button type="submit" icon="save">Enregistrer reglement</x-button></div></form>
@endsection
