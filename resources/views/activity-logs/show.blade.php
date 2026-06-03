@extends('layouts.app')

@section('title', 'Detail log | SFMID Gestion')
@section('subtitle', 'Audit')
@section('page-title', 'Detail du log')

@section('content')
    <div class="mb-6">
        <a href="{{ route('activity-logs.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Retour</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-5 lg:grid-cols-4">
            <div>
                <p class="text-xs font-bold uppercase text-slate-500">Date</p>
                <p class="mt-1 font-semibold">{{ $activityLog->created_at?->format('d/m/Y H:i:s') }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-slate-500">Utilisateur</p>
                <p class="mt-1 font-semibold">{{ $activityLog->user?->name ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-slate-500">Module</p>
                <p class="mt-1 font-semibold">{{ $activityLog->module ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-slate-500">Action</p>
                <p class="mt-1 font-semibold">{{ $activityLog->action }}</p>
            </div>
        </div>
        <p class="mt-6 text-sm text-slate-700">{{ $activityLog->description }}</p>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Anciennes valeurs</h3>
            <pre class="mt-4 max-h-[520px] overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($activityLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Nouvelles valeurs</h3>
            <pre class="mt-4 max-h-[520px] overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($activityLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </section>
    </div>
@endsection
