@extends('layouts.app')

@section('title', 'Clients | SFMID Gestion')
@section('subtitle', 'Gestion commerciale')
@section('page-title', 'Clients')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Liste des clients</h3>
            <p class="mt-1 text-sm text-slate-500">Fiches clients, conditions commerciales et historique.</p>
        </div>

        @can('create', \App\Models\Client::class)
            <x-button :href="route('clients.create')" icon="user-plus">Nouveau client</x-button>
        @endcan
    </div>

    <form method="GET" action="{{ route('clients.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Nom, code, telephone, email, IFU, RCCM..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                <select name="type" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
                    <option value="">Tous</option>
                    @foreach($types as $type)
                        <option value="{{ $type['value'] }}" @selected($filters['type'] === $type['value'])>{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
                    <option value="">Tous</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-button type="submit" icon="filter">Filtrer</x-button>
            <x-button :href="route('clients.index')" tone="secondary" icon="rotate-ccw">Reinitialiser</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Code</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Client</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Type</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Contact</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Statut</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-950">{{ $client->code }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $client->name }}</p>
                                <p class="text-xs text-slate-500">{{ $client->email ?: 'Email non renseigne' }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $client->type->label() }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $client->phone ?: 'Non renseigne' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $client->status->badgeClasses() }}">{{ $client->status->label() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <x-action-button :href="route('clients.show', $client)" icon="eye" label="Voir le client" />
                                    @can('update', $client)
                                        <x-action-button :href="route('clients.edit', $client)" icon="pencil" label="Modifier le client" tone="info" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun client trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">{{ $clients->links() }}</div>
    </div>
@endsection
