@extends('layouts.app')

@section('title', 'Journal d activite | SFMID Gestion')
@section('subtitle', 'Audit')
@section('page-title', 'Journal d activite')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">Liste des logs</h3>
            <p class="mt-1 text-sm text-slate-500">Trace des actions utilisateurs et systeme.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <x-button :href="route('activity-logs.csv', request()->query())" tone="secondary" icon="file-spreadsheet">CSV</x-button>
            <x-button :href="route('activity-logs.pdf', request()->query())" target="_blank" tone="secondary" icon="file-down">PDF</x-button>
        </div>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-6">
            <select name="user_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Tous utilisateurs</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected($filters['user_id'] === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <select name="module" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Tous modules</option>
                @foreach($modules as $module)
                    <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
                @endforeach
            </select>
            <select name="action" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Toutes actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <x-button type="submit" icon="filter">Filtrer</x-button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Date</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Utilisateur</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Module</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Action</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">Description</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-5 py-4">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4">{{ $log->user?->name ?: '-' }}</td>
                            <td class="px-5 py-4"><x-badge tone="slate">{{ $log->module ?: '-' }}</x-badge></td>
                            <td class="px-5 py-4">{{ $log->action }}</td>
                            <td class="px-5 py-4">{{ $log->description }}</td>
                            <td class="px-5 py-4 text-right">
                                <x-action-button :href="route('activity-logs.show', $log)" icon="eye" label="Voir le detail du log" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun log trouve.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-5 py-4">{{ $logs->links() }}</div>
    </div>
@endsection
