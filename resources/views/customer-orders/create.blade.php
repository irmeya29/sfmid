@extends('layouts.app')

@section('title', 'Nouveau BC client | SFMID Gestion')
@section('subtitle', 'Facturation')
@section('page-title', 'Nouveau bon de commande client')

@section('content')
    <form method="POST" action="{{ route('customer-orders.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="source_type" value="{{ $proforma ? 'proforma' : 'direct' }}">
        @if($proforma)
            <input type="hidden" name="proforma_id" value="{{ $proforma->id }}">
        @endif

        <x-card>
            <div class="grid gap-5 lg:grid-cols-3">
                @if($proforma)
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Source</p>
                        <p class="mt-1 font-semibold text-slate-950">{{ $proforma->number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Client</p>
                        <p class="mt-1 font-semibold text-slate-950">{{ $proforma->client?->name }}</p>
                    </div>
                @else
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Client</label>
                        <select name="client_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            <option value="">Selectionner</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->code }} - {{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Reference BC client</label>
                    <input name="customer_reference" value="{{ old('customer_reference') }}" placeholder="Ex : BC Mine A 2026-001" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date du BC</label>
                    <input type="date" name="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Piece jointe</label>
                    <input type="file" name="attachment" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Conditions confirmees</label>
                    <textarea name="confirmed_terms" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('confirmed_terms', $proforma?->payment_terms ?? $proforma?->terms) }}</textarea>
                </div>
            </div>
        </x-card>

        @if($proforma)
            <x-card>
                <h3 class="text-base font-semibold text-slate-950">Articles repris de la proforma</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr><th class="text-left">Reference</th><th class="text-left">Designation</th><th class="text-right">Qte</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @foreach($proforma->items as $item)
                                <tr>
                                    <td>{{ $item->client_product_reference ?: ($item->product_internal_reference ?: $item->product_code) }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-right">{{ \App\Support\NumberFormatter::quantity($item->quantity) }}</td>
                                    <td class="text-right">{{ number_format((float) $item->line_total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @else
            <x-card>
                <h3 class="text-base font-semibold text-slate-950">Articles commandes</h3>
                <div class="mt-4 space-y-3">
                    @for($i = 0; $i < 5; $i++)
                        <div class="grid gap-3 lg:grid-cols-[2fr_1fr_1fr]">
                            <select name="items[{{ $i }}][product_id]" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" @if($i === 0) required @endif>
                                <option value="">Article</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->internal_reference ?: $product->code }} - {{ $product->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="any" min="0.001" name="items[{{ $i }}][quantity]" value="{{ $i === 0 ? 1 : '' }}" placeholder="Quantite" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" placeholder="Prix unitaire" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        </div>
                    @endfor
                </div>
            </x-card>
        @endif

        <div class="flex gap-3">
            <x-button type="submit" icon="save">Enregistrer</x-button>
            <x-button :href="route('customer-orders.index')" tone="secondary" icon="x">Annuler</x-button>
        </div>
    </form>
@endsection
