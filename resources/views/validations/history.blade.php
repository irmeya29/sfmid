@extends('layouts.app')

@section('title', 'Historique validations | SFMID Gestion')
@section('subtitle', 'Centre de validations internes')
@section('page-title', 'Historique complet')

@section('content')
    <div class="mb-6 flex justify-end">
        <a href="{{ route('validations.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Validations en attente</a>
    </div>

    @include('validations._filters')

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-4 text-left">Date</th>
                    <th class="px-5 py-4 text-left">Document</th>
                    <th class="px-5 py-4 text-left">Action</th>
                    <th class="px-5 py-4 text-left">Statuts</th>
                    <th class="px-5 py-4 text-left">Utilisateur</th>
                    <th class="px-5 py-4 text-left">Motif/commentaire</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($histories as $entry)
                    <tr>
                        <td class="px-5 py-4">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ class_basename($entry->document_type) }}</p>
                            <p class="text-xs text-slate-500">#{{ $entry->document_id }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $entry->action->label() }}</span>
                        </td>
                        <td class="px-5 py-4">{{ $entry->from_status ?: '-' }} -> {{ $entry->to_status ?: '-' }}</td>
                        <td class="px-5 py-4">{{ $entry->user?->name ?: '-' }}</td>
                        <td class="px-5 py-4">{{ $entry->reason ?: $entry->comment ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun historique trouve.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-4">{{ $histories->links() }}</div>
    </div>
@endsection
