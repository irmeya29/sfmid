@php
    $currency = old('currency', $proforma->currency ?? ($company['sales.currency'] ?? 'FCFA'));
    $defaultTaxRate = (float) ($company['sales.default_tax_rate'] ?? 0);
    $moneyInput = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0';
    $productsPayload = $products->map(fn ($product) => [
        'id' => $product->id,
        'code' => $product->code,
        'internal_reference' => $product->internal_reference,
        'name' => $product->name,
        'unit' => $product->unit,
        'sale_price' => (float) $product->sale_price,
        'physical_stock' => (float) $product->physical_stock,
        'suspense_stock' => (float) $product->suspense_stock,
        'client_references' => $product->clientPrices->map(fn ($reference) => [
            'client_id' => $reference->client_id,
            'client_reference' => $reference->client_reference,
            'client_designation' => $reference->client_designation,
            'sale_price' => (float) $reference->sale_price,
            'discount_rate' => (float) $reference->discount_rate,
        ])->values(),
    ])->values();

    $clientsPayload = $clients->map(fn ($client) => [
        'id' => $client->id,
        'commercial_terms' => $client->commercial_terms,
        'delivery_sites' => $client->deliverySites->map(fn ($site) => [
            'id' => $site->id,
            'name' => $site->name,
            'address' => $site->address,
            'contact_name' => $site->contact_name,
            'contact_phone' => $site->contact_phone,
            'is_default' => (bool) $site->is_default,
        ])->values(),
    ])->values();

    $oldItems = old('items', $lineItems);
    $lineTaxRate = collect($oldItems)->pluck('tax_rate')->filter(fn ($value) => $value !== null && $value !== '')->first();
    $taxRate = old('tax_rate', $lineTaxRate ?? ($defaultTaxRate ?: 18));
    $applyTax = (bool) old('apply_tax', (float) $taxRate > 0);
@endphp

<form method="POST" action="{{ $action }}" class="max-w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Client <span class="text-red-600">*</span></label>
            <select id="client-select" name="client_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Selectionner un client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) old('client_id', $proforma->client_id) === (string) $client->id)>
                        {{ $client->code }} - {{ $client->name }}
                    </option>
                @endforeach
            </select>
            @error('client_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Date d'emission</label>
            <input type="date" name="issue_date" value="{{ old('issue_date', optional($proforma->issue_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Validite de l'offre</label>
            <input type="date" name="valid_until" value="{{ old('valid_until', optional($proforma->valid_until)->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </div>

        <div class="lg:col-span-3">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Objet <span class="text-red-600">*</span></label>
            <input name="subject" value="{{ old('subject', $proforma->subject) }}" required maxlength="255" placeholder="Ex : Fourniture de flexibles hydrauliques" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('subject')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Adresse / site concerne</label>
            <select id="delivery-site-select" name="client_delivery_site_id" data-selected="{{ old('client_delivery_site_id', $proforma->client_delivery_site_id) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Aucun site specifique</option>
            </select>
            <p id="delivery-site-help" class="mt-2 text-xs text-slate-500">Selectionnez un client.</p>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Incoterm / condition de vente</label>
            <select name="incoterm" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Non precise</option>
                @foreach(['EXW', 'DAP', 'DDP', 'Autres'] as $incoterm)
                    <option value="{{ $incoterm }}" @selected(old('incoterm', $proforma->incoterm) === $incoterm)>{{ $incoterm }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Devise</label>
            <input name="currency" value="{{ $currency }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Delai de livraison</label>
            <input name="delivery_delay" value="{{ old('delivery_delay', $proforma->delivery_delay) }}" placeholder="Ex : 7 jours apres commande" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Conditions de reglement</label>
            <textarea id="payment-terms-textarea" name="payment_terms" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('payment_terms', $proforma->payment_terms ?? $proforma->terms) }}</textarea>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
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
                <span class="text-xs text-slate-500">appliquee a tout le document</span>
            </div>
        </div>

        <div class="lg:col-span-3">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
            <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $proforma->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-8">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-950">Articles du devis</h3>
                <p class="mt-1 text-sm text-slate-500">Recherchez un article par code, designation, reference SFMID ou reference client/mine.</p>
            </div>
            <x-button type="button" id="add-line" tone="secondary" icon="plus">Ajouter une ligne</x-button>
        </div>

        <div class="max-w-full overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-[980px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Article</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Reference</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Qte</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">PU</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Remise %</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Total TTC</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
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
                            <td class="px-4 py-3">
                                <p class="client-ref text-sm font-semibold text-slate-800">-</p>
                                <p class="internal-ref text-xs text-slate-500">Ref SFMID : -</p>
                            </td>
                            <td class="px-3 py-3"><input type="number" step="any" min="0.001" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="quantity-input w-20 rounded-xl border border-slate-300 px-3 py-2 text-right text-sm" required><p class="stock-warning mt-1 hidden text-xs text-amber-700"></p></td>
                            <td class="px-3 py-3"><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" value="{{ $moneyInput($item['unit_price'] ?? 0) }}" class="price-input w-28 rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                            <td class="px-3 py-3"><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][discount_rate]" value="{{ $moneyInput($item['discount_rate'] ?? 0) }}" class="discount-input w-20 rounded-xl border border-slate-300 px-3 py-2 text-right text-sm"></td>
                            <td class="px-4 py-3 text-right"><p class="line-total font-semibold text-slate-950">0 {{ $currency }}</p><p class="line-detail text-xs text-slate-500"></p></td>
                            <td class="px-4 py-3 text-right"><x-action-button type="button" class="remove-line" icon="trash-2" label="Retirer la ligne" tone="danger" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex justify-between text-sm"><span>Total general HT</span><strong id="subtotal">0 {{ $currency }}</strong></div>
                <div class="mt-3 flex justify-between text-sm"><span>Total remise</span><strong id="discount-total">0 {{ $currency }}</strong></div>
                <div class="mt-3 flex justify-between text-sm"><span>Total TVA</span><strong id="tax-total">0 {{ $currency }}</strong></div>
                <div class="mt-4 border-t pt-4 flex justify-between"><span class="font-semibold">Total TTC</span><strong id="grand-total" class="text-xl text-slate-950">0 {{ $currency }}</strong></div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit" icon="save">{{ $submitLabel }}</x-button>
        <x-button :href="route('proformas.index')" tone="secondary" icon="x">Annuler</x-button>
    </div>
</form>

<script>
const products = @json($productsPayload);
const clients = @json($clientsPayload);
const currency = @json($currency);
const body = document.getElementById('items-body');
const clientSelect = document.getElementById('client-select');
const siteSelect = document.getElementById('delivery-site-select');
const siteHelp = document.getElementById('delivery-site-help');
const paymentTerms = document.getElementById('payment-terms-textarea');
const applyTax = document.getElementById('apply-tax');
const taxRateInput = document.getElementById('global-tax-rate');

const money = value => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value || 0) + ' ' + currency;
const qty = value => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 3 }).format(value || 0);
const inputNumber = value => {
    const number = Number(value || 0);
    return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/\.?0+$/, '');
};
const globalTaxRate = () => applyTax.checked ? Number(taxRateInput.value || 0) : 0;
const client = () => clients.find(item => String(item.id) === String(clientSelect.value));
const product = id => products.find(item => String(item.id) === String(id));
const clientRef = product => product?.client_references?.find(item => String(item.client_id) === String(clientSelect.value));

function refreshSites(fillDefault = false) {
    const selected = client();
    const current = siteSelect.dataset.selected || siteSelect.value;
    siteSelect.innerHTML = '<option value="">Aucun site specifique</option>';
    if (!selected || !selected.delivery_sites.length) {
        siteHelp.textContent = selected ? 'Aucun site enregistre pour ce client.' : 'Selectionnez un client.';
        return;
    }
    selected.delivery_sites.forEach(site => {
        const option = document.createElement('option');
        option.value = site.id;
        option.textContent = [site.name, site.address].filter(Boolean).join(' - ');
        siteSelect.appendChild(option);
    });
    const defaultSite = selected.delivery_sites.find(site => site.is_default);
    siteSelect.value = selected.delivery_sites.some(site => String(site.id) === String(current)) ? current : (fillDefault && defaultSite ? defaultSite.id : '');
    siteSelect.dataset.selected = siteSelect.value;
    siteHelp.textContent = 'Site repris sur le document.';
}

function searchProducts(term) {
    const needle = term.toLowerCase();
    return products.filter(product => [
        product.code,
        product.internal_reference,
        product.name,
        ...(product.client_references || []).flatMap(ref => [ref.client_reference, ref.client_designation])
    ].filter(Boolean).some(value => String(value).toLowerCase().includes(needle))).slice(0, 8);
}

function selectProduct(row, selectedProduct) {
    const ref = clientRef(selectedProduct);
    row.querySelector('.product-id-input').value = selectedProduct.id;
    row.querySelector('.product-search').value = selectedProduct.name;
    row.querySelector('.product-label').textContent = `${selectedProduct.code} - ${selectedProduct.unit} - Stock ${qty(selectedProduct.physical_stock)}`;
    row.querySelector('.client-ref').textContent = ref?.client_reference || selectedProduct.internal_reference || selectedProduct.code;
    row.querySelector('.internal-ref').textContent = `Ref SFMID : ${selectedProduct.internal_reference || selectedProduct.code}`;
    if (ref?.client_designation) row.querySelector('.product-search').value = ref.client_designation;
    if (ref?.sale_price) row.querySelector('.price-input').value = inputNumber(ref.sale_price);
    if (ref?.discount_rate) row.querySelector('.discount-input').value = inputNumber(ref.discount_rate);
    refreshRow(row);
}

function refreshRow(row) {
    const selectedProduct = product(row.querySelector('.product-id-input').value);
    if (selectedProduct) {
        const warning = row.querySelector('.stock-warning');
        const quantity = Number(row.querySelector('.quantity-input').value || 0);
        warning.classList.toggle('hidden', quantity <= Number(selectedProduct.physical_stock || 0));
        warning.textContent = 'Quantite superieure au stock physique.';
    }
    const quantity = Number(row.querySelector('.quantity-input').value || 0);
    const price = Number(row.querySelector('.price-input').value || 0);
    const discountRate = Number(row.querySelector('.discount-input').value || 0);
    const taxRate = globalTaxRate();
    const gross = quantity * price;
    const discount = gross * discountRate / 100;
    const ht = gross - discount;
    const tax = ht * taxRate / 100;
    const ttc = ht + tax;
    row.querySelector('.line-total').textContent = money(ttc);
    row.querySelector('.line-detail').textContent = taxRate > 0 ? `HT ${money(ht)} - TVA ${money(tax)}` : `HT ${money(ht)}`;
    refreshTotals();
}

function refreshTotals() {
    let gross = 0, discount = 0, tax = 0;
    const taxRate = globalTaxRate();
    body.querySelectorAll('.item-row').forEach(row => {
        const q = Number(row.querySelector('.quantity-input').value || 0);
        const p = Number(row.querySelector('.price-input').value || 0);
        const d = Number(row.querySelector('.discount-input').value || 0);
        const lineGross = q * p;
        const lineDiscount = lineGross * d / 100;
        const lineHt = lineGross - lineDiscount;
        gross += lineGross;
        discount += lineDiscount;
        tax += lineHt * taxRate / 100;
    });
    document.getElementById('subtotal').textContent = money(gross - discount);
    document.getElementById('discount-total').textContent = money(discount);
    document.getElementById('tax-total').textContent = money(tax);
    document.getElementById('grand-total').textContent = money(gross - discount + tax);
}

function reindex() {
    body.querySelectorAll('.item-row').forEach((row, index) => {
        row.querySelectorAll('input').forEach(input => {
            if (input.name) input.name = input.name.replace(/items\[\d+]/, `items[${index}]`);
        });
    });
}

function bind(row) {
    const search = row.querySelector('.product-search');
    const results = row.querySelector('.product-results');
    search.addEventListener('input', () => {
        const matches = searchProducts(search.value);
        results.innerHTML = matches.length
            ? matches.map(item => `<button type="button" class="flex w-full items-start justify-between gap-3 border-b border-slate-100 bg-white px-3 py-2.5 text-left last:border-b-0 hover:bg-[#EAF4FB]" data-id="${item.id}"><span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">${item.internal_reference || item.code} - ${item.name}</span><span class="block truncate text-xs text-slate-500">${item.code} - ${item.unit}</span></span><span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">${money(item.sale_price)}</span></button>`).join('')
            : '<div class="px-3 py-3 text-sm text-slate-500">Aucun article trouve.</div>';
        results.className = 'product-results absolute left-0 top-full z-50 mt-2 max-h-64 w-[min(36rem,calc(100vw-3rem))] min-w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/5';
        results.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            selectProduct(row, product(button.dataset.id));
            results.classList.add('hidden');
        }));
    });
    row.querySelectorAll('.quantity-input,.price-input,.discount-input').forEach(input => input.addEventListener('input', () => refreshRow(row)));
    row.querySelector('.remove-line').addEventListener('click', () => {
        if (body.querySelectorAll('.item-row').length > 1) row.remove();
        reindex();
        refreshTotals();
    });
    const selectedProduct = product(row.querySelector('.product-id-input').value);
    if (selectedProduct) selectProduct(row, selectedProduct);
    refreshRow(row);
}

document.getElementById('add-line').addEventListener('click', () => {
    const clone = body.querySelector('.item-row').cloneNode(true);
    clone.querySelectorAll('input').forEach(input => input.value = input.classList.contains('quantity-input') ? 1 : '');
    clone.querySelector('.line-total').textContent = money(0);
    clone.querySelector('.line-detail').textContent = '';
    clone.querySelector('.product-label').textContent = '';
    clone.querySelector('.client-ref').textContent = '-';
    clone.querySelector('.internal-ref').textContent = 'Ref SFMID : -';
    body.appendChild(clone);
    reindex();
    bind(clone);
});

clientSelect.addEventListener('change', () => {
    refreshSites(true);
    const selected = client();
    if (selected?.commercial_terms && !paymentTerms.value.trim()) paymentTerms.value = selected.commercial_terms;
    body.querySelectorAll('.item-row').forEach(row => {
        const selectedProduct = product(row.querySelector('.product-id-input').value);
        if (selectedProduct) selectProduct(row, selectedProduct);
    });
});
siteSelect.addEventListener('change', () => siteSelect.dataset.selected = siteSelect.value);
applyTax.addEventListener('change', () => body.querySelectorAll('.item-row').forEach(refreshRow));
taxRateInput.addEventListener('input', () => body.querySelectorAll('.item-row').forEach(refreshRow));
refreshSites(false);
body.querySelectorAll('.item-row').forEach(bind);
reindex();
refreshTotals();
</script>
