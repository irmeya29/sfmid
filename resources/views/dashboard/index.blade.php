@extends('layouts.app')

@section('title', 'Tableau de bord | SFMID Gestion')
@section('subtitle', 'Pilotage')
@section('page-title', 'Tableau de bord')

@section('content')
    @php
        $currency = 'FCFA';
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
        $years = range($filters['start_year'], max((int) now()->year + 1, $filters['start_year']));
    @endphp

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 lg:grid-cols-[180px_180px_150px_1fr_auto] lg:items-end">
            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-slate-500">Période</label>
                <select name="period" class="w-full border border-slate-300 px-3 py-2.5 text-sm font-bold">
                    <option value="month" @selected($filters['period'] === 'month')>Mois</option>
                    <option value="year" @selected($filters['period'] === 'year')>Année</option>
                    <option value="custom" @selected($filters['period'] === 'custom')>Dates libres</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-slate-500">Mois</label>
                <select name="month" class="w-full border border-slate-300 px-3 py-2.5 text-sm font-bold">
                    @foreach($months as $number => $label)
                        <option value="{{ $number }}" @selected($filters['month'] === $number)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-slate-500">Année</label>
                <select name="year" class="w-full border border-slate-300 px-3 py-2.5 text-sm font-bold">
                    @foreach($years as $year)
                        <option value="{{ $year }}" @selected($filters['year'] === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-slate-500">Du</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" min="{{ $filters['start_year'] }}-01-01" class="w-full border border-slate-300 px-3 py-2.5 text-sm font-bold">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-slate-500">Au</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" min="{{ $filters['start_year'] }}-01-01" class="w-full border border-slate-300 px-3 py-2.5 text-sm font-bold">
                </div>
            </div>

            <x-button type="submit" class="h-11">Appliquer</x-button>
        </form>
    </section>

    <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-bold text-slate-500">Vue consolidée</p>
            <h3 class="text-lg font-black text-slate-950">{{ $filters['period_label'] }}</h3>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()?->hasPermission('reports.view_finance'))
                <x-button :href="route('reports.index')" tone="secondary">Rapports</x-button>
            @endif
            @if(auth()->user()?->hasPermission('validations.view'))
                <x-button :href="route('validations.index')" tone="secondary">Validations</x-button>
            @endif
        </div>
    </div>

    <section class="grid gap-4 xl:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase text-slate-500">Facturé période</p>
                    <p class="mt-3 text-2xl font-black text-slate-950">{{ number_format((float) $stats['period_sales_amount'], 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs font-bold text-slate-400">{{ $currency }} · {{ number_format($stats['period_invoice_count'], 0, ',', ' ') }} facture(s)</p>
                </div>
                <span class="rounded-xl bg-slate-100 p-2 text-slate-500"><i data-lucide="receipt-text" class="h-5 w-5"></i></span>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase text-slate-500">Encaissements</p>
                    <p class="mt-3 text-2xl font-black text-emerald-700">{{ number_format((float) $stats['period_receipts_amount'], 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs font-bold text-slate-400">{{ $currency }}</p>
                </div>
                <span class="rounded-xl bg-emerald-50 p-2 text-emerald-700"><i data-lucide="arrow-down-left" class="h-5 w-5"></i></span>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase text-slate-500">Dépenses</p>
                    <p class="mt-3 text-2xl font-black text-red-700">{{ number_format((float) $stats['period_expenses_amount'], 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs font-bold text-slate-400">{{ $currency }}</p>
                </div>
                <span class="rounded-xl bg-red-50 p-2 text-red-700"><i data-lucide="arrow-up-right" class="h-5 w-5"></i></span>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-slate-950 p-5 text-white shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase text-slate-300">Solde période</p>
                    <p class="mt-3 text-2xl font-black">{{ number_format((float) $stats['period_balance'], 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs font-bold text-slate-400">{{ $currency }}</p>
                </div>
                <span class="rounded-xl bg-white/10 p-2 text-white"><i data-lucide="wallet" class="h-5 w-5"></i></span>
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <x-card title="Priorités" subtitle="Ce qui demande une action ou une surveillance.">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($alerts as $alert)
                    @php
                        $canSee = auth()->user()?->hasPermission($alert['permission']);
                    @endphp
                    <a href="{{ $canSee ? route($alert['route']) : '#' }}" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-white hover:shadow-sm">
                        <span class="font-black text-slate-800">{{ $alert['label'] }}</span>
                        <x-badge :tone="$alert['tone']">{{ number_format($alert['value'], 0, ',', ' ') }}</x-badge>
                    </a>
                @endforeach
            </div>
        </x-card>

        <x-card title="En cours" subtitle="Situation globale hors période.">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm font-bold text-slate-500">Créances ouvertes</span>
                    <span class="text-right font-black text-red-700">{{ number_format((float) $stats['unpaid_invoices_amount'], 0, ',', ' ') }} {{ $currency }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                    <span class="text-sm font-bold text-slate-500">Factures à encaisser</span>
                    <span class="font-black text-slate-950">{{ number_format($stats['unpaid_invoices_count'], 0, ',', ' ') }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                    <span class="text-sm font-bold text-slate-500">Clients actifs</span>
                    <span class="font-black text-slate-950">{{ number_format($stats['clients_count'], 0, ',', ' ') }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                    <span class="text-sm font-bold text-slate-500">Produits suivis</span>
                    <span class="font-black text-slate-950">{{ number_format($stats['products_count'], 0, ',', ' ') }}</span>
                </div>
            </div>
        </x-card>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-card title="Factures à suivre" subtitle="Les dernières factures avec solde restant dû.">
            @if($latestInvoices->isEmpty())
                <x-empty-state title="Aucune facture ouverte" message="Les factures à encaisser apparaîtront ici." />
            @else
                <div class="space-y-3">
                    @foreach($latestInvoices as $invoice)
                        <a href="{{ auth()->user()?->hasPermission('invoices.view') ? route('invoices.show', $invoice) : '#' }}" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-white hover:shadow-sm">
                            <div class="min-w-0">
                                <p class="truncate font-black text-slate-950">{{ $invoice->number }} · {{ $invoice->client?->name ?: '-' }}</p>
                                <p class="text-xs text-slate-500">Échéance : {{ $invoice->due_date?->format('d/m/Y') ?: '-' }}</p>
                            </div>
                            <p class="shrink-0 text-right font-black text-red-700">{{ number_format((float) $invoice->balance_due, 0, ',', ' ') }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card title="Stock bas" subtitle="Produits à réapprovisionner ou surveiller.">
            @if($lowStockProducts->isEmpty())
                <x-empty-state title="Aucun stock bas" message="Tous les produits suivis sont au-dessus de leur seuil." />
            @else
                <div class="space-y-3">
                    @foreach($lowStockProducts as $product)
                        <a href="{{ auth()->user()?->hasPermission('products.view') ? route('products.show', $product) : '#' }}" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-white hover:shadow-sm">
                            <div class="min-w-0">
                                <p class="truncate font-black text-slate-950">{{ $product->code }} · {{ $product->name }}</p>
                                <p class="text-xs text-slate-500">Seuil : {{ \App\Support\NumberFormatter::quantity($product->alert_threshold) }}</p>
                            </div>
                            <x-badge tone="red">{{ \App\Support\NumberFormatter::quantity($product->physical_stock) }}</x-badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>
    </section>
@endsection
