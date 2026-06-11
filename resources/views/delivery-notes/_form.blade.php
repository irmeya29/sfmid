@php
    $productsPayload = $products->map(fn ($product) => [
        'id' => $product->id,
        'code' => $product->code,
        'name' => $product->name,
        'unit' => $product->unit,
        'sale_price' => (float) $product->sale_price,
        'physical_stock' => (float) $product->physical_stock,
        'suspense_stock' => (float) $product->suspense_stock,
    ])->values();

    $clientsPayload = $clients->map(fn ($client) => [
        'id' => $client->id,
        'delivery_sites' => $client->deliverySites->map(fn ($site) => [
            'id' => $site->id,
            'name' => $site->name,
            'address' => $site->address,
            'is_default' => (bool) $site->is_default,
        ])->values(),
    ])->values();

    $oldItems = old('items', $lineItems);
@endphp

<form method="POST" action="{{ $action }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Client <span class="text-red-600">*</span></label>
            <select id="client-select" name="client_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                <option value="">Sélectionner un client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) old('client_id', $deliveryNote->client_id) === (string) $client->id)>
                        {{ $client->code }} - {{ $client->name }}
                    </option>
                @endforeach
            </select>
            @error('client_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Date prévue</label>
            <input type="date" name="planned_delivery_date" value="{{ old('planned_delivery_date', optional($deliveryNote->planned_delivery_date)->format('Y-m-d')) }}"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
            @error('planned_delivery_date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Objet <span class="text-red-600">*</span></label>
            <input name="subject" value="{{ old('subject', $deliveryNote->subject) }}" required maxlength="255" placeholder="Ex : Livraison de flexibles hydrauliques" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
            @error('subject') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Site de livraison</label>
            <select id="delivery-site-select" name="client_delivery_site_id"
                    data-selected="{{ old('client_delivery_site_id', $deliveryNote->client_delivery_site_id) }}"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                <option value="">Aucun site spécifique</option>
            </select>
            <p id="delivery-site-help" class="mt-2 text-sm text-slate-500">Sélectionnez d'abord un client.</p>
            @error('client_delivery_site_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
            <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">{{ old('notes', $deliveryNote->notes) }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-950">Produits</h3>
                <p class="mt-1 text-sm text-slate-500">La livraison déplacera le stock physique vers le stock en suspens.</p>
            </div>
            <x-button type="button" id="add-line" tone="secondary" icon="plus">Ajouter une ligne</x-button>
        </div>

        @error('items') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="min-w-[280px] px-4 py-3 text-left font-bold text-slate-600">Produit</th>
                        <th class="min-w-[120px] px-4 py-3 text-right font-bold text-slate-600">Stock</th>
                        <th class="min-w-[110px] px-4 py-3 text-right font-bold text-slate-600">Qté BL</th>
                        <th class="min-w-[130px] px-4 py-3 text-right font-bold text-slate-600">Qté livrée</th>
                        <th class="px-4 py-3 text-right font-bold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody id="items-body" class="divide-y divide-slate-100">
                    @foreach($oldItems as $index => $item)
                        <tr class="item-row">
                            <td class="px-4 py-3">
                                <select name="items[{{ $index }}][product_id]" class="product-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->code }} - {{ $product->name }}</option>
                                    @endforeach
                                </select>
                                @error("items.$index.product_id") <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="physical-stock font-semibold text-slate-700">0</span>
                                <p class="stock-warning mt-2 hidden text-xs font-semibold text-amber-700"></p>
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="any" min="0.001" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="quantity-input w-full rounded-xl border border-slate-300 px-3 py-2 text-right text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10" required>
                                @error("items.$index.quantity") <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="any" min="0.001" name="items[{{ $index }}][delivered_quantity]" value="{{ $item['delivered_quantity'] ?? ($item['quantity'] ?? 1) }}" class="delivered-quantity-input w-full rounded-xl border border-slate-300 px-3 py-2 text-right text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                                @error("items.$index.delivered_quantity") <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="hidden">
                                <input type="hidden" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" class="price-input">
                                <input type="hidden" name="items[{{ $index }}][discount_amount]" value="{{ $item['discount_amount'] ?? 0 }}" class="discount-input">
                                <span class="line-total">0 FCFA</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-action-button type="button" class="remove-line" icon="trash-2" label="Retirer la ligne" tone="danger" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="hidden">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex items-center justify-between text-sm"><span class="text-slate-500">Sous-total</span><span id="subtotal" class="font-bold text-slate-950">0 FCFA</span></div>
                <div class="mt-3 flex items-center justify-between text-sm"><span class="text-slate-500">Remise</span><span id="discount-total" class="font-bold text-slate-950">0 FCFA</span></div>
                <div class="mt-4 border-t border-slate-200 pt-4">
                    <div class="flex items-center justify-between"><span class="font-bold text-slate-700">Total général</span><span id="grand-total" class="text-xl font-black text-slate-950">0 FCFA</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit" icon="save">{{ $submitLabel }}</x-button>
        <a href="{{ route('delivery-notes.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Annuler</a>
    </div>
</form>

<script>
    const products = @json($productsPayload);
    const clients = @json($clientsPayload);
    const body = document.getElementById('items-body');
    const addLineButton = document.getElementById('add-line');
    const clientSelect = document.getElementById('client-select');
    const deliverySiteSelect = document.getElementById('delivery-site-select');
    const deliverySiteHelp = document.getElementById('delivery-site-help');

    function money(value) {
        return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value || 0) + ' FCFA';
    }
    function quantity(value) {
        return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 3 }).format(value || 0);
    }
    function findProduct(id) {
        return products.find(product => String(product.id) === String(id));
    }
    function findClient(id) {
        return clients.find(client => String(client.id) === String(id));
    }
    function refreshDeliverySites(fillDefault = false) {
        const selectedClient = findClient(clientSelect.value);
        const currentSelected = deliverySiteSelect.dataset.selected || deliverySiteSelect.value;
        deliverySiteSelect.innerHTML = '<option value="">Aucun site spécifique</option>';
        if (!selectedClient || selectedClient.delivery_sites.length === 0) {
            deliverySiteHelp.textContent = selectedClient ? 'Aucun site enregistré pour ce client.' : "Sélectionnez d'abord un client.";
            deliverySiteSelect.value = '';
            deliverySiteSelect.dataset.selected = '';
            return;
        }
        selectedClient.delivery_sites.forEach(site => {
            const option = document.createElement('option');
            option.value = site.id;
            option.textContent = [site.name, site.address].filter(Boolean).join(' - ');
            deliverySiteSelect.appendChild(option);
        });
        const defaultSite = selectedClient.delivery_sites.find(site => site.is_default);
        const hasCurrentSelected = selectedClient.delivery_sites.some(site => String(site.id) === String(currentSelected));
        deliverySiteSelect.value = hasCurrentSelected ? currentSelected : (fillDefault && defaultSite ? defaultSite.id : '');
        deliverySiteSelect.dataset.selected = deliverySiteSelect.value;
        deliverySiteHelp.textContent = 'Site et adresse repris sur les documents.';
    }
    function reindexRows() {
        body.querySelectorAll('.item-row').forEach((row, index) => {
            row.querySelectorAll('select, input').forEach(input => {
                input.name = input.name.replace(/items\[\d+]/, `items[${index}]`);
            });
        });
    }
    function refreshRow(row) {
        const product = findProduct(row.querySelector('.product-select').value);
        const qtyInput = row.querySelector('.quantity-input');
        const deliveredInput = row.querySelector('.delivered-quantity-input');
        const priceInput = row.querySelector('.price-input');
        const discountInput = row.querySelector('.discount-input');
        const stockWarning = row.querySelector('.stock-warning');

        if (product) {
            row.querySelector('.physical-stock').textContent = quantity(product.physical_stock);
            if (!priceInput.value || Number(priceInput.value) <= 0) {
                priceInput.value = product.sale_price;
            }
        } else {
            row.querySelector('.physical-stock').textContent = '0';
        }

        if (!deliveredInput.value) {
            deliveredInput.value = qtyInput.value || 1;
        }

        const deliveredQty = Number(deliveredInput.value || 0);
        const price = Number(priceInput.value || 0);
        const discount = Number(discountInput.value || 0);

        if (product && deliveredQty > Number(product.physical_stock || 0)) {
            stockWarning.textContent = 'Quantité livrée supérieure au stock physique.';
            stockWarning.classList.remove('hidden');
        } else {
            stockWarning.textContent = '';
            stockWarning.classList.add('hidden');
        }

        row.querySelector('.line-total').textContent = money((Number(qtyInput.value || 0) * price) - discount);
        refreshTotals();
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
        });
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('discount-total').textContent = money(discountTotal);
        document.getElementById('grand-total').textContent = money(subtotal - discountTotal);
    }
    function bindRow(row) {
        row.querySelectorAll('select, input').forEach(input => {
            input.addEventListener('change', () => refreshRow(row));
            input.addEventListener('input', () => refreshRow(row));
        });
        row.querySelector('.remove-line').addEventListener('click', () => {
            if (body.querySelectorAll('.item-row').length === 1) return;
            row.remove();
            reindexRows();
            refreshTotals();
        });
        refreshRow(row);
    }
    function addLine() {
        const clone = body.querySelector('.item-row').cloneNode(true);
        clone.querySelectorAll('input').forEach(input => {
            input.value = input.classList.contains('quantity-input') || input.classList.contains('delivered-quantity-input') ? 1 : 0;
        });
        clone.querySelector('.product-select').value = '';
        clone.querySelector('.physical-stock').textContent = '0';
        clone.querySelector('.line-total').textContent = '0 FCFA';
        clone.querySelector('.stock-warning').textContent = '';
        clone.querySelector('.stock-warning').classList.add('hidden');
        body.appendChild(clone);
        reindexRows();
        bindRow(clone);
        refreshTotals();
    }

    addLineButton.addEventListener('click', addLine);
    clientSelect.addEventListener('change', () => refreshDeliverySites(true));
    deliverySiteSelect.addEventListener('change', () => deliverySiteSelect.dataset.selected = deliverySiteSelect.value);
    refreshDeliverySites(false);
    body.querySelectorAll('.item-row').forEach(bindRow);
    reindexRows();
    refreshTotals();
</script>
