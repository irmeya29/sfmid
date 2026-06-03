<div class="grid gap-6 xl:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Ventes par periode</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['sales'] as $row)
                        <tr><td class="px-4 py-3">{{ $row->period }}</td><td class="px-4 py-3">{{ $row->count }} facture(s)</td><td class="px-4 py-3 text-right font-bold">{{ number_format((float) $row->total, 0, ',', ' ') }} FCFA</td></tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-slate-500">Aucune vente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Paiements encaisses</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['payments'] as $row)
                        <tr><td class="px-4 py-3">{{ $row->period }}</td><td class="px-4 py-3">{{ $row->count }} paiement(s)</td><td class="px-4 py-3 text-right font-bold">{{ number_format((float) $row->total, 0, ',', ' ') }} FCFA</td></tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-slate-500">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-bold text-slate-950">Factures impayees</h3>
    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse($report['unpaidInvoices'] as $invoice)
                    <tr><td class="px-4 py-3 font-semibold">{{ $invoice->number }}</td><td class="px-4 py-3">{{ $invoice->client?->name }}</td><td class="px-4 py-3">{{ $invoice->due_date?->format('d/m/Y') }}</td><td class="px-4 py-3 text-right font-bold">{{ number_format((float) $invoice->balance_due, 0, ',', ' ') }} FCFA</td></tr>
                @empty
                    <tr><td class="px-4 py-6 text-center text-slate-500">Aucune facture impayee.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Stock bas</h3>
        <div class="mt-4 space-y-2">
            @forelse($report['lowStock'] as $product)
                <div class="flex justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm"><span>{{ $product->code }} - {{ $product->name }}</span><strong>{{ \App\Support\NumberFormatter::quantity($product->physical_stock) }}</strong></div>
            @empty
                <p class="text-sm text-slate-500">Aucun stock bas.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Stock en suspens</h3>
        <div class="mt-4 space-y-2">
            @forelse($report['suspense'] as $row)
                <div class="grid gap-1 rounded-xl border border-slate-200 px-4 py-3 text-sm">
                    <div class="flex justify-between"><span>{{ $row->client?->name }}</span><strong>{{ \App\Support\NumberFormatter::quantity($row->remainingQuantity()) }}</strong></div>
                    <p class="text-xs text-slate-500">{{ $row->product?->code }} - {{ $row->product?->name }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun stock en suspens.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-bold text-slate-950">Depenses par categorie</h3>
    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse($report['expensesByCategory'] as $row)
            <div class="rounded-xl border border-slate-200 p-4 text-sm"><p class="font-semibold">{{ $row->category }}</p><p class="mt-2 text-xl font-bold">{{ number_format((float) $row->total, 0, ',', ' ') }} FCFA</p><p class="text-xs text-slate-500">{{ $row->count }} depense(s)</p></div>
        @empty
            <p class="text-sm text-slate-500">Aucune depense.</p>
        @endforelse
    </div>
</section>

@if($canViewMargin)
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Marge par produit</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['margin'] as $row)
                        <tr><td class="px-4 py-3 font-semibold">{{ $row->product_name }}</td><td class="px-4 py-3 text-right">{{ number_format((float) $row->sales_total, 0, ',', ' ') }}</td><td class="px-4 py-3 text-right font-bold">{{ number_format((float) $row->margin, 0, ',', ' ') }} FCFA</td></tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-slate-500">Aucune marge calculee.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
