@extends('layouts.app')

@section('title', 'Achats | SFMID')
@section('subtitle', 'Fournisseurs')
@section('page-title', 'Achats fournisseurs')

@section('content')
    <div class="mb-6 flex flex-wrap gap-3">
        @if(auth()->user()?->hasPermission('purchases.create_request'))
            <x-button :href="route('purchases.requests.create')" tone="secondary" icon="file-plus-2">Demande d'achat</x-button>
        @endif
        @if(auth()->user()?->hasPermission('purchases.create_order'))
            <x-button :href="route('purchases.orders.create')" icon="clipboard-check">Bon de commande</x-button>
        @endif
        @if(auth()->user()?->hasPermission('purchases.receive_invoice'))
            <x-button :href="route('purchases.invoices.create')" tone="secondary" icon="file-text">Facture fournisseur</x-button>
        @endif
        @if(auth()->user()?->hasPermission('purchases.pay_supplier'))
            <x-button :href="route('purchases.payments.create')" tone="secondary" icon="credit-card">Reglement</x-button>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[.9fr_1.1fr]">
        <x-card title="Demandes d'achat recentes" subtitle="Les dernieres demandes internes enregistrees.">
            @if($requests->isEmpty())
                <x-empty-state title="Aucune demande" message="Les demandes d'achat recentes apparaitront ici." />
            @else
                <div class="overflow-x-auto">
                    <x-table>
                        <thead>
                            <tr>
                                <th class="text-left">Numero</th>
                                <th class="text-left">Fournisseur</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                                <tr>
                                    <td class="font-semibold text-slate-950">{{ $request->number }}</td>
                                    <td>{{ $request->supplier?->name ?: '-' }}</td>
                                    <td>{{ $request->request_date?->format('d/m/Y') }}</td>
                                    <td><x-badge tone="blue">{{ $request->status }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            @endif
        </x-card>

        <x-card title="Dettes fournisseurs" subtitle="Factures fournisseur avec solde restant du.">
            @if($invoices->isEmpty())
                <x-empty-state title="Aucune dette fournisseur" message="Les dettes ouvertes apparaitront ici." />
            @else
                <div class="space-y-3">
                    @foreach($invoices as $invoice)
                        <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $invoice->number }}</p>
                                <p class="text-sm text-slate-500">{{ $invoice->supplier?->name }} - {{ $invoice->status }}</p>
                            </div>
                            <p class="text-right font-semibold text-red-700">{{ number_format((float) $invoice->balance_due, 0, ',', ' ') }} FCFA</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <x-card title="Bons de commande fournisseur" subtitle="Suivi des commandes envoyees aux fournisseurs." class="mt-6">
        @if($orders->isEmpty())
            <x-empty-state title="Aucun bon de commande" message="Creez un bon de commande fournisseur pour demarrer le suivi achat.">
                @if(auth()->user()?->hasPermission('purchases.create_order'))
                    <x-button :href="route('purchases.orders.create')" icon="clipboard-check">Creer un BC fournisseur</x-button>
                @endif
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <x-table>
                    <thead>
                        <tr>
                            <th class="text-left">Numero</th>
                            <th class="text-left">Fournisseur</th>
                            <th class="text-left">Statut</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td class="font-semibold text-slate-950">{{ $order->number }}</td>
                                <td>{{ $order->supplier?->name }}</td>
                                <td><x-badge tone="slate">{{ $order->status }}</x-badge></td>
                                <td class="text-right font-semibold">{{ number_format((float) $order->total, 0, ',', ' ') }} FCFA</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <x-action-button :href="route('purchases.orders.show', $order)" icon="eye" label="Voir le bon de commande" />
                                        @if(auth()->user()?->hasPermission('purchases.export_pdf'))
                                            <x-action-button :href="route('purchases.orders.pdf', $order)" target="_blank" icon="printer" label="Imprimer / PDF" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </x-card>
@endsection
