@extends('layouts.app')

@section('title', 'Nouveau paiement | SFMID Gestion')
@section('subtitle', 'Caisse')
@section('page-title', 'Nouveau paiement')

@section('content')
    <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="max-w-5xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="mb-6"><a href="{{ route('payments.index') }}" class="text-sm font-semibold text-slate-600">← Retour aux paiements</a></div>
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Facture</label>
                <select id="invoice-select" name="invoice_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Sélectionner une facture impayée</option>
                    @foreach($invoices as $item)
                        <option value="{{ $item->id }}" data-balance="{{ (float) $item->balance_due }}" @selected((string) old('invoice_id', $invoice?->id) === (string) $item->id)>{{ $item->number }} - {{ $item->client?->name }} - solde {{ number_format((float) $item->balance_due, 0, ',', ' ') }} FCFA</option>
                    @endforeach
                </select>
                @error('invoice_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-2 text-sm font-semibold text-slate-600">Solde facture : <span id="invoice-balance">0 FCFA</span></p>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Montant</label>
                <input id="amount-input" type="number" min="1" step="0.01" name="amount" value="{{ old('amount', $invoice?->balance_due) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                <p id="amount-warning" class="mt-2 hidden text-sm font-semibold text-red-600">Montant supérieur au solde.</p>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Date paiement</label>
                <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Mode</label>
                <select name="method" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">@foreach($paymentModes as $mode)<option value="{{ $mode->code }}" @selected(old('method') === $mode->code)>{{ $mode->name }}</option>@endforeach</select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Référence</label>
                <input name="reference" value="{{ old('reference') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Pièce justificative</label>
                <input type="file" name="attachment" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('attachment')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="mt-6 flex gap-3"><x-button type="submit" icon="save">Creer le paiement</x-button><x-button :href="route('payments.index')" tone="secondary" icon="x">Annuler</x-button></div>
    </form>
    <script>
        const invoiceSelect = document.getElementById('invoice-select');
        const balanceLabel = document.getElementById('invoice-balance');
        const amountInput = document.getElementById('amount-input');
        const warning = document.getElementById('amount-warning');
        function money(value){return new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0}).format(value||0)+' FCFA'}
        function refresh(){const balance=Number(invoiceSelect.selectedOptions[0]?.dataset.balance||0);balanceLabel.textContent=money(balance);warning.classList.toggle('hidden', Number(amountInput.value||0)<=balance)}
        invoiceSelect.addEventListener('change', refresh); amountInput.addEventListener('input', refresh); refresh();
    </script>
@endsection
