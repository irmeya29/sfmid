@extends('layouts.app')

@section('title', 'Transfert stock | SFMID Gestion')
@section('subtitle', 'Gestion du stock')
@section('page-title', 'Transfert stock')

@section('content')
    @include('stock._nav')

    <form method="POST" action="{{ route('stock.transfers.store') }}" class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Produit</label>
                <select name="product_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Selectionner</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                            {{ $product->code }} - {{ $product->name }} | Physique {{ \App\Support\NumberFormatter::quantity($product->physical_stock) }} | Outil {{ \App\Support\NumberFormatter::quantity($product->tool_stock) }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Site source</label>
                <select name="from_stock_site_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @foreach($stockSites as $site)
                        <option value="{{ $site->id }}" @selected((string) old('from_stock_site_id', $defaultStockSiteId) === (string) $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
                @error('from_stock_site_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Site destination</label>
                <select name="to_stock_site_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @foreach($stockSites as $site)
                        <option value="{{ $site->id }}" @selected((string) old('to_stock_site_id') === (string) $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
                @error('to_stock_site_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Stock concerne</label>
                <select name="stock_column" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="physical_stock" @selected(old('stock_column', 'physical_stock') === 'physical_stock')>Stock physique</option>
                    <option value="tool_stock" @selected(old('stock_column') === 'tool_stock')>Stock outil</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Quantite</label>
                <input type="number" step="any" min="0.001" name="quantity" value="{{ old('quantity', 1) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('quantity')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Motif</label>
                <textarea name="reason" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('reason') }}</textarea>
                @error('reason')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <x-button type="submit" icon="repeat-2">Transferer</x-button>
            <a href="{{ route('stock.movements') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Annuler</a>
        </div>
    </form>
@endsection
