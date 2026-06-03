@extends('layouts.app')

@section('title', 'Modifier facture | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Modifier facture')

@section('content')
    <div class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">← Retour à la facture</a>
        </div>
        <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="grid gap-5">
            @csrf
            @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date facture</label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', $invoice->issue_date?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('issue_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Échéance</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('due_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Conditions de paiement</label>
                <textarea name="payment_terms" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('payment_terms', $invoice->payment_terms) }}</textarea>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
            <div class="flex gap-3">
                <x-button type="submit" icon="save">Enregistrer</x-button>
                <a href="{{ route('invoices.show', $invoice) }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Annuler</a>
            </div>
        </form>
    </div>
@endsection
