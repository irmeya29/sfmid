<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('stock.physical') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Physique</a>
    <a href="{{ route('stock.reserved') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Réservé</a>
    <a href="{{ route('stock.suspense') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">En suspens</a>
    <a href="{{ route('stock.tool') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Outil</a>
    <a href="{{ route('stock.movements') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Mouvements</a>
    <a href="{{ route('stock.transfers.create') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Transfert</a>
    @if(auth()->user()?->hasAnyPermission(['stock.manage_sites', 'settings.update_stock_rules']))
        <a href="{{ route('stock-sites.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Sites</a>
    @endif
    <a href="{{ route('stock.reports.low-stock') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Stock bas</a>
</div>
