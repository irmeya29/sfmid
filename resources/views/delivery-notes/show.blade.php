@extends('layouts.app')

@section('title', $deliveryNote->number.' | SFMID Gestion')
@section('subtitle', 'Détail BL')
@section('page-title', $deliveryNote->number)

@section('content')
    @php
        $qty = fn ($value) => number_format((float) $value, (float) $value == floor((float) $value) ? 0 : 3, ',', ' ');
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('delivery-notes.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Retour aux BL</a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $deliveryNote->status->badgeClasses() }}">{{ $deliveryNote->status->label() }}</span>
                <span class="text-sm text-slate-500">Date prévue : {{ $deliveryNote->planned_delivery_date?->format('d/m/Y') ?: 'Non planifiée' }}</span>
                @if($deliveryNote->stock_moved_at)
                    <span class="text-sm font-semibold text-indigo-700">Stock déplacé le {{ $deliveryNote->stock_moved_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            @can('exportPdf', $deliveryNote)
                <x-button :href="route('delivery-notes.pdf', $deliveryNote)" target="_blank" tone="secondary" icon="printer">PDF / Imprimer</x-button>
            @endcan
            @can('update', $deliveryNote)
                <x-button :href="route('delivery-notes.edit', $deliveryNote)" icon="pencil">Modifier</x-button>
            @endcan
            @can('submit', $deliveryNote)
                <form method="POST" action="{{ route('delivery-notes.submit', $deliveryNote) }}">@csrf
                    <x-button type="submit" tone="secondary" icon="send">Soumettre</x-button>
                </form>
            @endcan
            @can('validate', $deliveryNote)
                <form method="POST" action="{{ route('delivery-notes.validate', $deliveryNote) }}">@csrf
                    <x-button type="submit" tone="success" icon="check">Valider</x-button>
                </form>
            @endcan
            @can('markPrepared', $deliveryNote)
                <form method="POST" action="{{ route('delivery-notes.mark-prepared', $deliveryNote) }}">@csrf
                    <x-button type="submit" tone="secondary" icon="package-check">Marquer préparé</x-button>
                </form>
            @endcan
            @can('convertToInvoice', $deliveryNote)
                @if(auth()->user()?->hasPermission('invoices.create'))
                    <form method="POST" action="{{ route('invoices.store') }}">
                        @csrf
                        <input type="hidden" name="source_type" value="delivery_note">
                        <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
                        <x-button type="submit" tone="success" icon="file-plus-2">Créer facture</x-button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @if($deliveryNote->rejection_reason)
        <x-alert type="error" :message="'Motif de rejet : '.$deliveryNote->rejection_reason" />
    @endif

    @include('commercial-documents._status-admin', [
        'action' => route('delivery-notes.status.update', $deliveryNote),
        'currentStatus' => $deliveryNote->status,
        'statuses' => \App\Enums\DeliveryNoteStatus::cases(),
        'title' => 'Administration du statut BL',
    ])

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card title="Client et livraison" class="lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Client</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->client?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Code client</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->client?->code }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Téléphone</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->client?->phone ?: 'Non renseigné' }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-slate-500">Objet</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->subject ?: $deliveryNote->proforma?->subject ?: 'Non renseigne' }}</p></div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Source</p>
                    <p class="mt-1 font-semibold text-slate-950">
                        @if($deliveryNote->customerOrder)
                            BC {{ $deliveryNote->customerOrder->customer_reference ?: $deliveryNote->customerOrder->number }}
                        @elseif($deliveryNote->proforma)
                            {{ $deliveryNote->proforma->number }}
                        @else
                            Creation manuelle
                        @endif
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase text-slate-500">Site / adresse</p>
                    <p class="mt-1 font-semibold text-slate-950">
                        @if($deliveryNote->deliverySite)
                            {{ $deliveryNote->deliverySite->name }}
                            @if($deliveryNote->deliverySite->address)<span class="font-normal text-slate-600">- {{ $deliveryNote->deliverySite->address }}</span>@endif
                        @else
                            {{ $deliveryNote->delivery_address ?: 'Non renseigné' }}
                        @endif
                    </p>
                </div>
            </div>
        </x-card>

        <x-card title="État livraison">
            <div class="space-y-4">
                <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Articles</span><span class="font-bold text-slate-950">{{ $deliveryNote->items->count() }}</span></div>
                <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Quantité BL</span><span class="font-bold text-slate-950">{{ $qty($deliveryNote->items->sum('quantity')) }}</span></div>
                <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Quantité livrée</span><span class="font-bold text-slate-950">{{ $qty($deliveryNote->items->sum('delivered_quantity')) }}</span></div>
                <div class="border-t border-slate-200 pt-4">
                    <div class="flex items-center justify-between"><span class="text-sm font-bold text-slate-700">Facture liée</span><span class="font-black text-slate-950">{{ $deliveryNote->invoice?->number ?: '-' }}</span></div>
                </div>
            </div>
        </x-card>
    </div>

    <x-card title="Articles à livrer" class="mt-6">
        <x-table>
            <thead>
                <tr>
                    <th class="text-left">Désignation</th>
                    <th class="text-right">Qté BL</th>
                    <th class="text-right">Qté livrée</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryNote->items as $item)
                    <tr>
                        <td><p class="font-semibold text-slate-950">{{ $item->product_name }}</p><p class="text-xs text-slate-500">{{ $item->product_code }} · {{ $item->unit }}</p></td>
                        <td class="text-right font-semibold">{{ $qty($item->quantity) }}</td>
                        <td class="text-right font-semibold">{{ $qty($item->delivered_quantity) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>

    @can('markDelivered', $deliveryNote)
        <x-card title="Marquer livré" subtitle="Cette action déplace le stock physique vers le stock en suspens et ne peut pas être répétée." class="mt-6 border-indigo-200">
            <form method="POST" action="{{ route('delivery-notes.mark-delivered', $deliveryNote) }}" class="grid gap-4 lg:grid-cols-2">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nom réceptionnaire <span class="text-red-600">*</span></label>
                    <input name="receiver_name" value="{{ old('receiver_name') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('receiver_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Téléphone</label>
                    <input name="receiver_phone" value="{{ old('receiver_phone') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('receiver_phone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date livraison <span class="text-red-600">*</span></label>
                    <input type="datetime-local" name="delivered_at" value="{{ old('delivered_at', now()->format('Y-m-d\TH:i')) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('delivered_at') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Adresse de réception</label>
                    <input name="delivery_address" value="{{ old('delivery_address', $deliveryNote->deliverySite?->address) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('delivery_address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="lg:col-span-2">
                    <x-button type="submit" tone="success" icon="truck">Confirmer la livraison</x-button>
                </div>
            </form>
        </x-card>
    @endcan

    @if($deliveryNote->delivered_at)
        <x-card title="Réception" class="mt-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Réceptionnaire</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->receiver_name ?: 'Non renseigné' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Téléphone</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->receiver_phone ?: 'Non renseigné' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->delivered_at->format('d/m/Y H:i') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Adresse</p><p class="mt-1 font-semibold text-slate-950">{{ $deliveryNote->delivery_address ?: 'Non renseignée' }}</p></div>
            </div>
        </x-card>
    @endif

    @can('reject', $deliveryNote)
        <x-card title="Rejeter le BL" class="mt-6 border-red-200">
            <form method="POST" action="{{ route('delivery-notes.reject', $deliveryNote) }}">
                @csrf
                <textarea name="reason" rows="3" required placeholder="Motif de rejet..." class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <x-button type="submit" tone="danger" icon="x" class="mt-3">Rejeter</x-button>
            </form>
        </x-card>
    @endcan

    <x-card title="Historique de validation" class="mt-6">
        <div class="space-y-3">
            @forelse($deliveryNote->validationHistories as $history)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="font-semibold text-slate-800">{{ $history->action->label() }}</p>
                        <p class="text-xs text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->user?->name ?? 'Système' }}</p>
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
