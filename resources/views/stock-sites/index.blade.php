@extends('layouts.app')

@section('title', 'Sites de stock | SFMID Gestion')
@section('subtitle', 'Gestion du stock')
@section('page-title', 'Sites de stock')

@section('content')
    @include('stock._nav')

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-950">{{ $site->exists ? 'Modifier le site' : 'Nouveau site' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Un site peut stocker, vendre, ou faire les deux.</p>
                </div>
                @if($site->exists)
                    <a href="{{ route('stock-sites.index') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">Nouveau</a>
                @endif
            </div>

            <form method="POST" action="{{ $action }}" class="space-y-4">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nom du site</label>
                    <input name="name" value="{{ old('name', $site->name) }}" required maxlength="255" placeholder="Ex : Ouaga magasin" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Code</label>
                    <input name="code" value="{{ old('code', $site->code) }}" maxlength="80" placeholder="Auto si vide" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase">
                    @error('code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Ex : Stock principal Ouagadougou">{{ old('description', $site->description) }}</textarea>
                    @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex min-h-[72px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="hidden" name="can_store" value="0">
                        <input type="checkbox" name="can_store" value="1" @checked(old('can_store', $site->can_store)) class="h-4 w-4 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-bold text-slate-950">Stockage</span>
                            <span class="text-xs text-slate-500">Peut recevoir du stock</span>
                        </span>
                    </label>

                    <label class="flex min-h-[72px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="hidden" name="can_sell" value="0">
                        <input type="checkbox" name="can_sell" value="1" @checked(old('can_sell', $site->can_sell)) class="h-4 w-4 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-bold text-slate-950">Vente</span>
                            <span class="text-xs text-slate-500">Peut servir les BL</span>
                        </span>
                    </label>

                    <label class="flex min-h-[72px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $site->exists ? $site->is_active : true)) class="h-4 w-4 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-bold text-slate-950">Actif</span>
                            <span class="text-xs text-slate-500">Visible aux operations</span>
                        </span>
                    </label>

                    <label class="flex min-h-[72px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $site->is_default)) class="h-4 w-4 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-bold text-slate-950">Par defaut</span>
                            <span class="text-xs text-slate-500">Entrees stock</span>
                        </span>
                    </label>
                </div>

                @error('can_store')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @error('can_sell')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @error('is_default')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Si un site est autorise a vendre, il pourra etre selectionne sur les BL. Sinon il sert uniquement au stockage et aux transferts.
                </div>

                <x-button type="submit" icon="save" class="w-full">{{ $submitLabel }}</x-button>
            </form>
        </section>

        <section class="min-w-0">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-950">Organisation actuelle</h3>
                    <p class="mt-1 text-sm text-slate-500">Les ventes sortent uniquement des sites avec la capacite Vente.</p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <p class="font-black text-slate-950">{{ $sites->count() }}</p>
                        <p class="text-slate-500">Sites</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <p class="font-black text-slate-950">{{ $sites->where('can_sell', true)->where('is_active', true)->count() }}</p>
                        <p class="text-slate-500">Vente</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <p class="font-black text-slate-950">{{ $sites->where('can_store', true)->where('is_active', true)->count() }}</p>
                        <p class="text-slate-500">Stock</p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-4 text-left">Site</th>
                                <th class="px-5 py-4 text-left">Role</th>
                                <th class="px-5 py-4 text-right">Physique</th>
                                <th class="px-5 py-4 text-right">Suspens</th>
                                <th class="px-5 py-4 text-right">Outil</th>
                                <th class="px-5 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($sites as $row)
                                <tr class="{{ $row->is_active ? 'hover:bg-slate-50' : 'bg-slate-50/70 text-slate-500' }}">
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $row->can_sell ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                                <i data-lucide="{{ $row->can_sell ? 'store' : 'warehouse' }}" class="h-4 w-4"></i>
                                            </span>
                                            <span>
                                                <span class="block font-bold text-slate-950">{{ $row->name }}</span>
                                                <span class="text-xs text-slate-500">{{ $row->code }}</span>
                                                @if($row->description)
                                                    <span class="mt-1 block max-w-md text-xs text-slate-500">{{ $row->description }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @if($row->can_store)
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">Stockage</span>
                                            @endif
                                            @if($row->can_sell)
                                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Vente</span>
                                            @endif
                                            @if($row->is_default)
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Defaut</span>
                                            @endif
                                            @unless($row->is_active)
                                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">Inactif</span>
                                            @endunless
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-bold">{{ \App\Support\NumberFormatter::quantity($row->physical_stock_total ?? 0) }}</td>
                                    <td class="px-5 py-4 text-right font-bold">{{ \App\Support\NumberFormatter::quantity($row->suspense_stock_total ?? 0) }}</td>
                                    <td class="px-5 py-4 text-right font-bold">{{ \App\Support\NumberFormatter::quantity($row->tool_stock_total ?? 0) }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <x-action-button :href="route('stock-sites.edit', $row)" icon="pencil" label="Modifier" />

                                            @unless($row->is_default)
                                                <form method="POST" action="{{ route('stock-sites.default', $row) }}">
                                                    @csrf
                                                    <x-action-button type="submit" icon="star" label="Definir par defaut" tone="warning" />
                                                </form>
                                            @endunless

                                            <form method="POST" action="{{ route('stock-sites.toggle', $row) }}">
                                                @csrf
                                                <x-action-button type="submit" icon="{{ $row->is_active ? 'pause' : 'play' }}" label="{{ $row->is_active ? 'Desactiver' : 'Activer' }}" tone="{{ $row->is_active ? 'danger' : 'success' }}" />
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun site cree.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
