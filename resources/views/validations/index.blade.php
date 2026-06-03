@extends('layouts.app')

@section('title', 'Validations | SFMID Gestion')
@section('subtitle', 'Centre de validations internes')
@section('page-title', 'Validations en attente')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-950">File de validation</h3>
            <p class="mt-1 text-sm text-slate-500">Proformas, BL, factures, paiements, depenses et mouvements stock.</p>
        </div>
        <x-button :href="route('validations.history', request()->query())" tone="secondary" icon="history">Historique complet</x-button>
    </div>

    @include('validations._filters')

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left">Document</th>
                    <th class="px-5 py-4 text-left">Createur</th>
                    <th class="px-5 py-4 text-left">Soumis le</th>
                    <th class="px-5 py-4 text-right">Montant/Qté</th>
                    <th class="px-5 py-4 text-left">Priorite</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $item['type_label'] }}</span>
                                @if($item['sensitive'])
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Sensible</span>
                                @endif
                            </div>
                            <p class="mt-2 font-semibold">{{ $item['number'] }}</p>
                            <p class="text-xs text-slate-500">{{ $item['title'] ?: '-' }}</p>
                        </td>
                        <td class="px-5 py-4">{{ $item['creator'] ?: '-' }}</td>
                        <td class="px-5 py-4">{{ $item['submitted_at']?->format('d/m/Y H:i') ?: '-' }}</td>
                        <td class="px-5 py-4 text-right font-bold">
                            @if($item['amount'] !== null)
                                {{ number_format($item['amount'], 0, ',', ' ') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @php($priorityClass = $item['priority'] === 'Urgent' ? 'bg-orange-100 text-orange-700' : ($item['priority'] === 'Sensible' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'))
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $priorityClass }}">{{ $item['priority'] }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-action-button :href="$item['show_route']" icon="eye" label="Voir le document" />
                                @if($item['can_validate'])
                                    <form method="POST" action="{{ route('validations.validate', [$item['type'], $item['id']]) }}">
                                        @csrf
                                        <x-action-button type="submit" icon="check" label="Valider" tone="success" />
                                    </form>
                                @endif
                                @if($item['can_reject'])
                                    <form method="POST" action="{{ route('validations.reject', [$item['type'], $item['id']]) }}" class="flex gap-2">
                                        @csrf
                                        <input type="text" name="reason" placeholder="Motif rejet" class="w-36 rounded-lg border border-slate-300 px-3 py-2 text-xs" required>
                                        <x-action-button type="submit" icon="x" label="Rejeter" tone="danger" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucune validation en attente.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-4">{{ $items->links() }}</div>
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-950">Dernières validations</h3>
            <a href="{{ route('validations.history') }}" class="text-sm font-bold text-slate-700">Tout voir</a>
        </div>
        <div class="space-y-3">
            @forelse($history as $entry)
                <div class="grid gap-2 rounded-xl border border-slate-200 p-4 text-sm md:grid-cols-[1fr_auto]">
                    <div>
                        <p class="font-semibold">{{ $entry->action->label() }} - {{ class_basename($entry->document_type) }} #{{ $entry->document_id }}</p>
                        <p class="text-xs text-slate-500">{{ $entry->from_status ?: '-' }} -> {{ $entry->to_status ?: '-' }} par {{ $entry->user?->name ?: '-' }}</p>
                    </div>
                    <p class="text-xs text-slate-500">{{ $entry->created_at?->format('d/m/Y H:i') }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun historique pour le moment.</p>
            @endforelse
        </div>
    </section>
@endsection
