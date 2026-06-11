@extends('layouts.app')

@section('title', $proforma->number.' | SFMID Gestion')
@section('subtitle', 'Detail proforma')
@section('page-title', $proforma->number)

@section('content')
    @php
        $currency = $proforma->currency ?: 'FCFA';
        $money = fn ($value) => number_format((float) $value, 0, ',', ' ').' '.$currency;
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('proformas.index') }}" class="text-sm font-semibold text-slate-600 hover:text-[#2676B3]">
                Retour aux proformas
            </a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $proforma->status->badgeClasses() }}">
                    {{ $proforma->status->label() }}
                </span>
                <span class="text-sm text-slate-500">Date : {{ $proforma->issue_date?->format('d/m/Y') ?: '-' }}</span>
                <span class="text-sm text-slate-500">Validite : {{ $proforma->valid_until?->format('d/m/Y') ?: '-' }}</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('exportPdf', $proforma)
                <x-action-button :href="route('proformas.pdf', $proforma)" target="_blank" icon="printer" label="PDF / Imprimer" tone="secondary" />
            @endcan
            @can('update', $proforma)
                <x-action-button :href="route('proformas.edit', $proforma)" icon="pencil" label="Modifier" />
            @endcan
            @can('submit', $proforma)
                <form method="POST" action="{{ route('proformas.submit', $proforma) }}" class="inline-flex">
                    @csrf
                    <x-action-button type="submit" icon="send" label="Soumettre" tone="secondary" />
                </form>
            @endcan
            @can('validate', $proforma)
                <form method="POST" action="{{ route('proformas.validate', $proforma) }}" class="inline-flex">
                    @csrf
                    <x-action-button type="submit" icon="check" label="Valider" tone="success" />
                </form>
            @endcan
            @can('convertToDeliveryNote', $proforma)
                <form method="POST" action="{{ route('proformas.convert-to-delivery-note', $proforma) }}" class="inline-flex">
                    @csrf
                    <x-action-button type="submit" icon="truck" label="Convertir en BL" tone="success" />
                </form>
            @endcan
            @if($proforma->status === \App\Enums\DocumentStatus::Validated)
                <x-action-button :href="route('customer-orders.create', ['proforma_id' => $proforma->id])" icon="clipboard-check" label="Creer un BC client" tone="secondary" />
                <form method="POST" action="{{ route('proformas.convert-to-invoice', $proforma) }}" class="inline-flex">
                    @csrf
                    <x-action-button type="submit" icon="file-badge" label="Creer une facture" tone="secondary" />
                </form>
            @endif
        </div>
    </div>

    @if($proforma->rejection_reason)
        <x-alert tone="danger" title="Motif de rejet" class="mb-6">
            {{ $proforma->rejection_reason }}
        </x-alert>
    @endif

    @include('commercial-documents._status-admin', [
        'action' => route('proformas.status.update', $proforma),
        'currentStatus' => $proforma->status,
        'statuses' => \App\Enums\DocumentStatus::cases(),
        'title' => 'Administration du statut proforma',
    ])

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">Client</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $proforma->client?->code ?: '-' }}</p>
                </div>
                <x-action-button :href="route('clients.show', $proforma->client)" icon="external-link" label="Ouvrir la fiche client" tone="secondary" />
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nom</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $proforma->client?->name ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $proforma->client?->phone ?: 'Non renseigne' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $proforma->client?->email ?: 'Non renseigne' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Adresse / site</p>
                    <p class="mt-1 font-semibold text-slate-950">
                        @if($proforma->deliverySite)
                            {{ $proforma->deliverySite->name }}
                            @if($proforma->deliverySite->address)
                                <span class="font-normal text-slate-600">- {{ $proforma->deliverySite->address }}</span>
                            @endif
                        @else
                            {{ $proforma->client?->address ?: 'Non renseigne' }}
                        @endif
                    </p>
                </div>
            </div>
        </x-card>

        <x-card>
            <h3 class="text-base font-semibold text-slate-950">Conditions commerciales</h3>
            <div class="mt-5 space-y-3 text-sm">
                <div class="border-b border-slate-200 pb-3">
                    <p class="text-slate-500">Objet</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $proforma->subject ?: 'Non renseigne' }}</p>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-slate-500">Incoterm</span>
                    <strong class="text-right text-slate-950">{{ $proforma->incoterm ?: '-' }}</strong>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-slate-500">Delai livraison</span>
                    <strong class="text-right text-slate-950">{{ $proforma->delivery_delay ?: '-' }}</strong>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-slate-500">Devise</span>
                    <strong class="text-right text-slate-950">{{ $currency }}</strong>
                </div>
                <div class="border-t border-slate-200 pt-3">
                    <p class="text-slate-500">Reglement</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $proforma->payment_terms ?: $proforma->terms ?: 'Non renseigne' }}</p>
                </div>
            </div>
        </x-card>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-950">Articles du devis</h3>
                <p class="mt-1 text-sm text-slate-500">Reference client/mine prioritaire, sinon reference interne SFMID.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[980px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Reference</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Designation</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Qte</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">PU</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Remise</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">TVA</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Total TTC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($proforma->items as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $item->client_product_reference ?: ($item->product_internal_reference ?: $item->product_code) }}</p>
                                <p class="text-xs text-slate-500">SFMID : {{ $item->product_internal_reference ?: $item->product_code }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $item->product_name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->unit }}</p>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold">{{ \App\Support\NumberFormatter::quantity($item->quantity) }}</td>
                            <td class="px-5 py-4 text-right">{{ $money($item->unit_price) }}</td>
                            <td class="px-5 py-4 text-right">
                                {{ number_format((float) $item->discount_rate, 2, ',', ' ') }} %
                                <p class="text-xs text-slate-500">{{ $money($item->discount_amount) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                {{ number_format((float) ($item->tax_rate ?? 0), 2, ',', ' ') }} %
                                <p class="text-xs text-slate-500">{{ $money($item->tax_amount ?? 0) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-slate-950">
                                {{ $money($item->line_total_ttc ?? $item->line_total) }}
                                <p class="text-xs font-normal text-slate-500">HT {{ $money($item->line_total_ht ?? (($item->line_subtotal ?? 0) - $item->discount_amount)) }}</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <h3 class="text-base font-semibold text-slate-950">Notes</h3>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $proforma->notes ?: 'Aucune note.' }}</p>
        </x-card>

        <x-card>
            <h3 class="text-base font-semibold text-slate-950">Totaux</h3>
            <div class="mt-5 space-y-3">
                <div class="flex justify-between text-sm"><span class="text-slate-500">Total brut</span><strong>{{ $money($proforma->subtotal) }}</strong></div>
                <div class="flex justify-between text-sm"><span class="text-slate-500">Remise</span><strong>{{ $money($proforma->discount_total) }}</strong></div>
                <div class="flex justify-between text-sm"><span class="text-slate-500">Total HT</span><strong>{{ $money($proforma->subtotal - $proforma->discount_total) }}</strong></div>
                <div class="flex justify-between text-sm"><span class="text-slate-500">TVA</span><strong>{{ $money($proforma->tax_total) }}</strong></div>
                <div class="border-t border-slate-200 pt-4">
                    <div class="flex justify-between">
                        <span class="font-semibold text-slate-700">Total TTC</span>
                        <strong class="text-xl font-semibold text-[#2676B3]">{{ $money($proforma->total) }}</strong>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    @can('reject', $proforma)
        <x-card class="mt-6 border-red-200">
            <h3 class="text-base font-semibold text-red-700">Rejeter la proforma</h3>
            <p class="mt-1 text-sm text-slate-500">Le motif est obligatoire et restera visible dans l'historique.</p>
            <form method="POST" action="{{ route('proformas.reject', $proforma) }}" class="mt-4">
                @csrf
                <textarea name="reason" rows="3" required placeholder="Motif de rejet..." class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/10">{{ old('reason') }}</textarea>
                @error('reason')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                <x-button type="submit" tone="danger" icon="x" class="mt-3">Rejeter</x-button>
            </form>
        </x-card>
    @endcan

    <x-card class="mt-6">
        <h3 class="text-base font-semibold text-slate-950">Historique de validation</h3>
        <div class="mt-5 space-y-3">
            @forelse($proforma->validationHistories as $history)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="font-semibold text-slate-800">{{ $history->action->label() }}</p>
                        <p class="text-xs text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }} - {{ $history->user?->name ?? 'Systeme' }}</p>
                    </div>
                    @if($history->reason)<p class="mt-2 text-sm text-red-700">{{ $history->reason }}</p>@endif
                    @if($history->comment)<p class="mt-2 text-sm text-slate-600">{{ $history->comment }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun historique pour le moment.</p>
            @endforelse
        </div>
    </x-card>
@endsection
