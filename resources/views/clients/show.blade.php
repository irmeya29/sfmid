@extends('layouts.app')

@section('title', $client->name.' | SFMID Gestion')
@section('subtitle', 'Fiche client')
@section('page-title', $client->name)

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('clients.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                ← Retour aux clients
            </a>
            <p class="mt-3 text-sm text-slate-500">Code client : <span class="font-bold text-slate-700">{{ $client->code }}</span></p>
        </div>

        <div class="flex flex-wrap gap-3">
            @can('update', $client)
                <x-button :href="route('clients.edit', $client)" icon="pencil">Modifier</x-button>
            @endcan

            @can('delete', $client)
                <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Supprimer ce client ? Cette action le masquera sans suppression définitive.');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" tone="danger" icon="trash-2">Supprimer</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-base font-bold text-slate-950">Informations client</h3>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nom</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->name }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Type</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->type->label() }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Téléphone</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->phone ?: 'Non renseigné' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->email ?: 'Non renseigné' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">IFU</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->ifu ?: 'Non renseigné' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">RCCM</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->rccm ?: 'Non renseigné' }}</p>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Adresse</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->address ?: 'Non renseignée' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-950">Statut commercial</h3>

            <div class="mt-5 space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</p>
                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $client->status->badgeClasses() }}">
                        {{ $client->status->label() }}
                    </span>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Délai de paiement</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $client->payment_delay_days }} jour(s)</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conditions</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $client->commercial_terms ?: 'Aucune condition spécifique.' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Proformas</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ $client->proformas->count() }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">BL</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ $client->deliveryNotes->count() }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Factures</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ $client->invoices->count() }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Solde dû</p>
            <p class="mt-3 text-2xl font-bold text-red-600">
                {{ number_format((float) $client->invoices->sum('balance_due'), 0, ',', ' ') }} FCFA
            </p>
        </div>
    </div>
@endsection
