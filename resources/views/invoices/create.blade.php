@php
    $currency = old('currency', $company['sales.currency'] ?? 'FCFA');
    $defaultTaxRate = (float) ($company['sales.default_tax_rate'] ?? 0);
    $moneyInput = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0';
    $clientsPayload = $clients->map(fn ($client) => [
        'id' => $client->id,
        'commercial_terms' => $client->commercial_terms,
        'payment_delay_days' => $client->payment_delay_days,
    ])->values();

    $oldItems = old('items', $lineItems);
    $lineTaxRate = collect($oldItems)->pluck('tax_rate')->filter(fn ($value) => $value !== null && $value !== '')->first();
    $taxRate = old('tax_rate', $lineTaxRate ?? ($defaultTaxRate ?: 18));
    $applyTax = (bool) old('apply_tax', (float) $taxRate > 0);
    $oldSourceType = old('source_type', $selectedDeliveryNoteId ? 'delivery_note' : ($selectedProformaId ? 'proforma' : 'direct'));
    $directStockEnabled = (bool) old('direct_stock_enabled', false);
    $selectedStockSiteId = old('stock_site_id', optional($stockSites->first())->id);
@endphp

@extends('layouts.app')

@section('title', 'Nouvelle facture | SFMID Gestion')
@section('subtitle', 'Cycle commercial')
@section('page-title', 'Nouvelle facture')

@section('content')
    <form method="POST" action="{{ route('invoices.store') }}" class="max-w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
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

            <div class="mt-5 grid gap-5 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Client</label>
                    <select id="client-select" name="client_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
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
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Echeance</label>
                    <input id="due-date" type="date" name="due_date" value="{{ old('due_date') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Objet <span class="text-red-600">*</span></label>
                    <input id="direct-subject" name="subject" value="{{ old('subject') }}" required maxlength="255" placeholder="Ex : Facturation fourniture de flexibles hydrauliques" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('subject')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Devise</label>
                    <input name="currency" value="{{ $currency }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Delai de livraison</label>
                    <input name="delivery_delay" value="{{ old('delivery_delay') }}" placeholder="Ex : immediat" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                </div>
                <div class="lg:col-span-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-950">Vente directe avec sortie stock</h4>
                            <p class="mt-1 text-sm text-slate-600">A activer pour les ventes comptoir ou clients particuliers sans BL.</p>
                        </div>
                        <label class="inline-flex cursor-pointer items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-sm">
                            <input type="hidden" name="direct_stock_enabled" value="0">
                            <input id="direct-stock-enabled" type="checkbox" name="direct_stock_enabled" value="1" class="h-4 w-4 rounded border-slate-300" @checked($directStockEnabled)>
                            <span class="text-sm font-bold text-slate-800">Sortir du stock a la validation</span>
                        </label>
                    </div>
                    <div id="direct-stock-site-wrap" class="mt-4 {{ $directStockEnabled ? '' : 'hidden' }}">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Site de vente</label>
                        <select id="stock-site-select" name="stock_site_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            <option value="">Selectionner un site de vente</option>
                            @foreach($stockSites as $site)
                                <option value="{{ $site->id }}" @selected((string) $selectedStockSiteId === (string) $site->id)>{{ $site->name }}</option>
                            @endforeach
                        </select>
                        @error('stock_site_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Conditions de reglement</label>
                    <textarea id="payment-terms" name="payment_terms" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('payment_terms') }}</textarea>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <input type="hidden" name="apply_tax" value="0">
                    <div class="flex items-center justify-between gap-3">
                        <label for="apply-tax" class="text-sm font-semibold text-slate-800">TVA globale</label>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input id="apply-tax" type="checkbox" name="apply_tax" value="1" class="peer sr-only" @checked($applyTax)>
                            <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-[#0F6B8A]"></span>
                            <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                        </label>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <input id="global-tax-rate" type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ $moneyInput($taxRate) }}" class="w-24 rounded-xl border border-slate-300 px-3 py-2 text-right text-sm">
                        <span class="text-sm font-semibold text-slate-600">%</span>
                        <span class="text-xs text-slate-500">sur toute la facture</span>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-8">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-950">Articles</h3>
                    <x-button type="button" id="add-line" tone="secondary" icon="plus">Ajouter une ligne</x-button>
                </div>
                @error('items')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror
                <div class="max-w-full overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table class="min-w-[980px] divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Article</th>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-right">Stock site</th>
                                <th class="px-4 py-3 text-right">Qte</th>
                                <th class="px-4 py-3 text-right">Prix</th>
                                <th class="px-4 py-3 text-right">Remise</th>
                                <th class="px-4 py-3 text-right">Total TTC</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-100">
                            @foreach($oldItems as $index => $item)
                                <tr class="item-row">
                                    <td class="px-3 py-3">
                                        <div class="relative">
                                            <input type="hidden" class="product-id-input" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] ?? '' }}">
                                            <input type="text" class="product-search w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Rechercher article..." autocomplete="off">
                                            <div class="product-results hidden"></div>
                                        </div>
                                        <p class="product-label mt-2 text-xs text-slate-500"></p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <p class="client-ref text-sm font-semibold text-slate-800">-</p>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <p class="site-stock font-bold text-slate-700">-</p>
                                        <p class="stock-warning hidden text-xs font-semibold text-amber-700"></p>
                                    </td>
                                    <td class="px-3 py-3"><input type="number" step="any" min="0.001" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="quantity-input w-20 rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                                    <td class="px-3 py-3"><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" value="{{ $moneyInput($item['unit_price'] ?? 0) }}" class="price-input w-28 rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                                    <td class="px-3 py-3"><input type="number" step="0.01" min="0" name="items[{{ $index }}][discount_amount]" value="{{ $moneyInput($item['discount_amount'] ?? 0) }}" class="discount-input w-24 rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                                    <td class="px-3 py-3 text-right"><p class="line-total font-bold text-slate-950">0 {{ $currency }}</p><p class="line-detail text-xs text-slate-500"></p></td>
                                    <td class="px-3 py-3 text-right"><x-action-button type="button" class="remove-line" icon="trash-2" label="Retirer la ligne" tone="danger" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex justify-between text-sm"><span class="text-slate-500">Total HT</span><span id="subtotal" class="font-bold">0 {{ $currency }}</span></div>
                        <div class="mt-3 flex justify-between text-sm"><span class="text-slate-500">Remise</span><span id="discount-total" class="font-bold">0 {{ $currency }}</span></div>
                        <div class="mt-3 flex justify-between text-sm"><span class="text-slate-500">TVA</span><span id="tax-total" class="font-bold">0 {{ $currency }}</span></div>
                        <div class="mt-4 border-t pt-4 flex justify-between"><span class="font-bold">Total TTC</span><span id="grand-total" class="text-xl font-black">0 {{ $currency }}</span></div>
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
        const clients = @json($clientsPayload);
        const currency = @json($currency);
        const searchUrl = @json(route('products.search.document-lines'));
        const deliveryPanel = document.getElementById('delivery-note-panel');
        const proformaPanel = document.getElementById('proforma-panel');
        const directPanel = document.getElementById('direct-panel');
        const body = document.getElementById('items-body');
        const clientSelect = document.getElementById('client-select');
        const paymentTerms = document.getElementById('payment-terms');
        const applyTax = document.getElementById('apply-tax');
        const taxRateInput = document.getElementById('global-tax-rate');
        const directSubject = document.getElementById('direct-subject');
        const directStockEnabled = document.getElementById('direct-stock-enabled');
        const directStockSiteWrap = document.getElementById('direct-stock-site-wrap');
        const stockSiteSelect = document.getElementById('stock-site-select');

        const money = value => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value || 0) + ' ' + currency;
        const inputNumber = value => {
            const number = Number(value || 0);
            return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/\.?0+$/, '');
        };
        const globalTaxRate = () => applyTax.checked ? Number(taxRateInput.value || 0) : 0;
        const selectedClient = () => clients.find(item => String(item.id) === String(clientSelect.value));
        const directStockActive = () => directStockEnabled?.checked && document.querySelector('.source-radio:checked').value === 'direct';

        function siteStockForRow(row) {
            if (!directStockActive()) return null;
            const siteStocks = JSON.parse(row.dataset.siteStocks || '{}');
            const siteStock = siteStocks[String(stockSiteSelect.value)];
            return siteStock ? Number(siteStock.physical_stock || 0) : 0;
        }

        function refreshStockDisplay(row) {
            const stockDisplay = row.querySelector('.site-stock');
            const warning = row.querySelector('.stock-warning');
            const stock = siteStockForRow(row);
            const qty = Number(row.querySelector('.quantity-input').value || 0);

            if (stock === null || !row.querySelector('.product-id-input').value) {
                stockDisplay.textContent = '-';
                warning.textContent = '';
                warning.classList.add('hidden');
                return;
            }

            stockDisplay.textContent = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 3 }).format(stock);

            if (qty > stock) {
                warning.textContent = 'Stock insuffisant';
                warning.classList.remove('hidden');
            } else {
                warning.textContent = '';
                warning.classList.add('hidden');
            }
        }

        function toggleSource() {
            const source = document.querySelector('.source-radio:checked').value;
            deliveryPanel.classList.toggle('hidden', source !== 'delivery_note');
            proformaPanel.classList.toggle('hidden', source !== 'proforma');
            directPanel.classList.toggle('hidden', source !== 'direct');
            directSubject.disabled = source !== 'direct';
            directSubject.required = source === 'direct';
            directStockSiteWrap.classList.toggle('hidden', !directStockEnabled.checked || source !== 'direct');
            body.querySelectorAll('.item-row').forEach(refreshStockDisplay);
        }

        async function searchProducts(term, productId = null) {
            const params = new URLSearchParams({ q: term || '', client_id: clientSelect.value || '' });
            if (productId) params.set('product_id', productId);
            const response = await fetch(`${searchUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
            return response.ok ? await response.json() : [];
        }

        function selectProduct(row, product, forcePrice = true) {
            const previousProductId = row.querySelector('.product-id-input').value;
            row.querySelector('.product-id-input').value = product.id;
            row.querySelector('.product-search').value = product.client_designation || product.name;
            row.querySelector('.product-label').textContent = `${product.code} - ${product.unit}`;
            row.querySelector('.client-ref').textContent = product.client_reference || product.code;
            row.dataset.siteStocks = JSON.stringify(product.site_stocks || {});
            const price = row.querySelector('.price-input');
            if (forcePrice || !price.value || Number(price.value) <= 0 || String(previousProductId) !== String(product.id)) {
                price.value = inputNumber(product.sale_price);
            }
            refreshStockDisplay(row);
            refreshTotals();
        }

        function refreshTotals() {
            let subtotal = 0, discountTotal = 0, taxTotal = 0;
            const taxRate = globalTaxRate();
            body.querySelectorAll('.item-row').forEach(row => {
                const qty = Number(row.querySelector('.quantity-input').value || 0);
                const price = Number(row.querySelector('.price-input').value || 0);
                const discount = Number(row.querySelector('.discount-input').value || 0);
                const gross = qty * price;
                const ht = Math.max(0, gross - discount);
                const tax = ht * taxRate / 100;
                subtotal += ht;
                discountTotal += discount;
                taxTotal += tax;
                row.querySelector('.line-total').textContent = money(ht + tax);
                row.querySelector('.line-detail').textContent = taxRate > 0 ? `HT ${money(ht)} - TVA ${money(tax)}` : `HT ${money(ht)}`;
                refreshStockDisplay(row);
            });
            document.getElementById('subtotal').textContent = money(subtotal);
            document.getElementById('discount-total').textContent = money(discountTotal);
            document.getElementById('tax-total').textContent = money(taxTotal);
            document.getElementById('grand-total').textContent = money(subtotal + taxTotal);
        }

        function reindexRows() {
            body.querySelectorAll('.item-row').forEach((row, index) => {
                row.querySelectorAll('input').forEach(input => {
                    if (input.name) input.name = input.name.replace(/items\[\d+]/, `items[${index}]`);
                });
            });
        }

        function bindRow(row) {
            const search = row.querySelector('.product-search');
            const results = row.querySelector('.product-results');
            search.addEventListener('input', async () => {
                const matches = await searchProducts(search.value);
                results.innerHTML = matches.length
                    ? matches.map(item => `<button type="button" class="flex w-full items-start justify-between gap-3 border-b border-slate-100 bg-white px-3 py-2.5 text-left last:border-b-0 hover:bg-[#EAF4FB]" data-id="${item.id}"><span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">${item.client_reference || item.code} - ${item.client_designation || item.name}</span><span class="block truncate text-xs text-slate-500">${item.code} - ${item.unit}</span></span><span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">${money(item.sale_price)}</span></button>`).join('')
                    : '<div class="px-3 py-3 text-sm text-slate-500">Aucun article trouve.</div>';
                results.className = 'product-results absolute left-0 top-full z-50 mt-2 max-h-64 w-[min(36rem,calc(100vw-3rem))] min-w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/5';
                results.querySelectorAll('button').forEach(button => button.addEventListener('click', async () => {
                    const matches = await searchProducts('', button.dataset.id);
                    if (matches[0]) selectProduct(row, matches[0], true);
                    results.classList.add('hidden');
                }));
            });
            row.querySelectorAll('.quantity-input,.price-input,.discount-input').forEach(input => input.addEventListener('input', refreshTotals));
            row.querySelector('.remove-line').addEventListener('click', () => {
                if (body.querySelectorAll('.item-row').length === 1) return;
                row.remove();
                reindexRows();
                refreshTotals();
            });
            const productId = row.querySelector('.product-id-input').value;
            if (productId) searchProducts('', productId).then(matches => matches[0] && selectProduct(row, matches[0], false));
        }

        document.querySelectorAll('.source-radio').forEach(radio => radio.addEventListener('change', toggleSource));
        document.getElementById('add-line').addEventListener('click', () => {
            const clone = body.querySelector('.item-row').cloneNode(true);
            clone.querySelectorAll('input').forEach(input => input.value = input.classList.contains('quantity-input') ? 1 : '');
            clone.querySelector('.line-total').textContent = money(0);
            clone.querySelector('.line-detail').textContent = '';
            clone.querySelector('.product-label').textContent = '';
            clone.querySelector('.client-ref').textContent = '-';
            clone.querySelector('.site-stock').textContent = '-';
            clone.querySelector('.stock-warning').textContent = '';
            clone.querySelector('.stock-warning').classList.add('hidden');
            clone.dataset.siteStocks = '{}';
            body.appendChild(clone);
            reindexRows();
            bindRow(clone);
            refreshTotals();
        });
        clientSelect.addEventListener('change', () => {
            const client = selectedClient();
            if (client?.commercial_terms && !paymentTerms.value.trim()) paymentTerms.value = client.commercial_terms;
            body.querySelectorAll('.item-row').forEach(row => {
                const productId = row.querySelector('.product-id-input').value;
                if (productId) searchProducts('', productId).then(matches => matches[0] && selectProduct(row, matches[0], true));
            });
        });
        applyTax.addEventListener('change', refreshTotals);
        taxRateInput.addEventListener('input', refreshTotals);
        directStockEnabled.addEventListener('change', toggleSource);
        stockSiteSelect.addEventListener('change', () => body.querySelectorAll('.item-row').forEach(refreshStockDisplay));
        body.querySelectorAll('.item-row').forEach(bindRow);
        toggleSource();
        refreshTotals();
    </script>
@endsection
