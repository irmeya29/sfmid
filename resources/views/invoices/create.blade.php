@php
    $productsPayload = $products->map(fn ($product) => [
        'id' => $product->id,
        'code' => $product->code,
        'name' => $product->name,
        'unit' => $product->unit,
        'sale_price' => (float) $product->sale_price,
    ])->values();

    $oldItems = old('items', $lineItems);
    $oldSourceType = old('source_type', $selectedDeliveryNoteId ? 'delivery_note' : ($selectedProformaId ? 'proforma' : 'direct'));
@endphp

@extends('layouts.app')

@section('title', 'Nouvelle facture | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Nouvelle facture')

@section('content')
    <form method="POST" action="{{ route('invoices.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <a href="{{ route('invoices.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Retour aux factures</a>
            <div class="inline-flex flex-wrap rounded-xl border border-slate-200 bg-slate-50 p-1 text-sm font-bold">
                <label class="cursor-pointer rounded-lg px-4 py-2 has-[:checked]:bg-white has-[:checked]:shadow-sm">
                    <input type="radio" name="source_type" value="direct" class="sr-only source-radio" @checked($oldSourceType === 'direct')>
                    Facture directe
                </label>
                <label class="cursor-pointer rounded-lg px-4 py-2 has-[:checked]:bg-white has-[:checked]:shadow-sm">
                    <input type="radio" name="source_type" value="delivery_note" class="sr-only source-radio" @checked($oldSourceType === 'delivery_note')>
                    Depuis BL livre
                </label>
                <label class="cursor-pointer rounded-lg px-4 py-2 has-[:checked]:bg-white has-[:checked]:shadow-sm">
                    <input type="radio" name="source_type" value="proforma" class="sr-only source-radio" @checked($oldSourceType === 'proforma')>
                    Depuis proforma
                </label>
            </div>
        </div>

        <section id="delivery-note-panel" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-base font-bold text-slate-950">Creer depuis un BL livre</h3>
            <p class="mt-1 text-sm text-slate-500">Le BL livre alimente la facture sans nouveau mouvement de stock.</p>
            <div class="mt-5">
                <label class="mb-2 block text-sm font-semibold text-slate-700">BL livre a facturer</label>
                <select name="delivery_note_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Selectionner un BL livre non facture</option>
                    @foreach($deliveryNotes as $deliveryNote)
                        <option value="{{ $deliveryNote->id }}" @selected((string) old('delivery_note_id', $selectedDeliveryNoteId) === (string) $deliveryNote->id)>
                            {{ $deliveryNote->number }} - {{ $deliveryNote->client?->name }} - {{ number_format((float) $deliveryNote->total, 0, ',', ' ') }} FCFA
                        </option>
                    @endforeach
                </select>
                @error('delivery_note_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </section>

        <section id="proforma-panel" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-base font-bold text-slate-950">Creer depuis une proforma validee</h3>
            <p class="mt-1 text-sm text-slate-500">La facture reprend l'offre commerciale validee. Aucun paiement automatique.</p>
            <div class="mt-5">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Proforma a facturer</label>
                <select name="proforma_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Selectionner une proforma validee</option>
                    @foreach($proformas as $proforma)
                        <option value="{{ $proforma->id }}" @selected((string) old('proforma_id', $selectedProformaId) === (string) $proforma->id)>
                            {{ $proforma->number }} - {{ $proforma->client?->name }} - {{ number_format((float) $proforma->total, 0, ',', ' ') }} FCFA
                        </option>
                    @endforeach
                </select>
                @error('proforma_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </section>

        <section id="direct-panel" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-base font-bold text-slate-950">Facture independante</h3>
            <p class="mt-1 text-sm text-slate-500">Pour une vente directe, par exemple un client particulier qui vient sur place.</p>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Client</label>
                    <select name="client_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <option value="">Selectionner un client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->code }} - {{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date facture</label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('issue_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Echeance</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('due_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Conditions de paiement</label>
                    <input name="payment_terms" value="{{ old('payment_terms') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-8">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-950">Articles</h3>
                    <x-button type="button" id="add-line" tone="secondary" icon="plus">Ajouter une ligne</x-button>
                </div>
                @error('items')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="min-w-[280px] px-4 py-3 text-left">Produit</th>
                                <th class="min-w-[120px] px-4 py-3 text-right">Qte</th>
                                <th class="min-w-[150px] px-4 py-3 text-right">Prix</th>
                                <th class="min-w-[140px] px-4 py-3 text-right">Remise</th>
                                <th class="min-w-[150px] px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-100">
                            @foreach($oldItems as $index => $item)
                                <tr class="item-row">
                                    <td class="px-4 py-3">
                                        <select name="items[{{ $index }}][product_id]" class="product-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                            <option value="">Selectionner</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->code }} - {{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3"><input type="number" step="any" min="0.001" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="quantity-input w-full rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                                    <td class="px-4 py-3"><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" class="price-input w-full rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                                    <td class="px-4 py-3"><input type="number" step="0.01" min="0" name="items[{{ $index }}][discount_amount]" value="{{ $item['discount_amount'] ?? 0 }}" class="discount-input w-full rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                                    <td class="px-4 py-3 text-right"><span class="line-total font-bold text-slate-950">0 FCFA</span></td>
                                    <td class="px-4 py-3 text-right"><x-action-button type="button" class="remove-line" icon="trash-2" label="Retirer la ligne" tone="danger" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex justify-between text-sm"><span class="text-slate-500">Sous-total</span><span id="subtotal" class="font-bold">0 FCFA</span></div>
                        <div class="mt-3 flex justify-between text-sm"><span class="text-slate-500">Remise</span><span id="discount-total" class="font-bold">0 FCFA</span></div>
                        <div class="mt-4 border-t pt-4 flex justify-between"><span class="font-bold">Total general</span><span id="grand-total" class="text-xl font-black">0 FCFA</span></div>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-6 flex gap-3">
            <x-button type="submit" icon="save">Creer la facture</x-button>
            <x-button :href="route('invoices.index')" tone="secondary" icon="x">Annuler</x-button>
        </div>
    </form>

    <script>
        const products = @json($productsPayload);
        const deliveryPanel = document.getElementById('delivery-note-panel');
        const proformaPanel = document.getElementById('proforma-panel');
        const directPanel = document.getElementById('direct-panel');
        const body = document.getElementById('items-body');

        function money(value) {
            return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value || 0) + ' FCFA';
        }
        function product(id) {
            return products.find(item => String(item.id) === String(id));
        }
        function toggleSource() {
            const source = document.querySelector('.source-radio:checked').value;
            deliveryPanel.classList.toggle('hidden', source !== 'delivery_note');
            proformaPanel.classList.toggle('hidden', source !== 'proforma');
            directPanel.classList.toggle('hidden', source !== 'direct');
        }
        function reindexRows() {
            body.querySelectorAll('.item-row').forEach((row, index) => {
                row.querySelectorAll('select, input').forEach(input => input.name = input.name.replace(/items\[\d+]/, `items[${index}]`));
            });
        }
        function refreshTotals() {
            let subtotal = 0;
            let discountTotal = 0;
            body.querySelectorAll('.item-row').forEach(row => {
                const qty = Number(row.querySelector('.quantity-input').value || 0);
                const price = Number(row.querySelector('.price-input').value || 0);
                const discount = Number(row.querySelector('.discount-input').value || 0);
                subtotal += qty * price;
                discountTotal += discount;
                row.querySelector('.line-total').textContent = money((qty * price) - discount);
            });
            document.getElementById('subtotal').textContent = money(subtotal);
            document.getElementById('discount-total').textContent = money(discountTotal);
            document.getElementById('grand-total').textContent = money(subtotal - discountTotal);
        }
        function bindRow(row) {
            row.querySelector('.product-select').addEventListener('change', event => {
                const selected = product(event.target.value);
                const price = row.querySelector('.price-input');
                if (selected && (!price.value || Number(price.value) <= 0)) price.value = selected.sale_price;
                refreshTotals();
            });
            row.querySelectorAll('input').forEach(input => input.addEventListener('input', refreshTotals));
            row.querySelector('.remove-line').addEventListener('click', () => {
                if (body.querySelectorAll('.item-row').length === 1) return;
                row.remove();
                reindexRows();
                refreshTotals();
            });
        }
        document.querySelectorAll('.source-radio').forEach(radio => radio.addEventListener('change', toggleSource));
        document.getElementById('add-line').addEventListener('click', () => {
            const clone = body.querySelector('.item-row').cloneNode(true);
            clone.querySelector('.product-select').value = '';
            clone.querySelectorAll('input').forEach(input => input.value = input.classList.contains('quantity-input') ? 1 : 0);
            clone.querySelector('.line-total').textContent = '0 FCFA';
            body.appendChild(clone);
            reindexRows();
            bindRow(clone);
            refreshTotals();
        });
        body.querySelectorAll('.item-row').forEach(bindRow);
        toggleSource();
        refreshTotals();
    </script>
@endsection
