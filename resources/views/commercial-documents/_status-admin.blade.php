@props([
    'action',
    'currentStatus',
    'statuses',
    'title' => 'Administration du statut',
])

@if(auth()->user()?->hasPermission('sensitive.update_validated_document'))
    <x-card class="mt-6 border-amber-200">
        <div class="flex flex-col gap-1">
            <h3 class="text-base font-semibold text-slate-950">{{ $title }}</h3>
            <p class="text-sm text-slate-500">Action sensible reservee aux administrateurs et tracee dans l'historique.</p>
        </div>

        <form method="POST" action="{{ $action }}" class="mt-4 grid gap-3 lg:grid-cols-[220px_1fr_auto] lg:items-end">
            @csrf
            @method('PATCH')

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Nouveau statut</label>
                <select name="status" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected($currentStatus === $status)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Motif</label>
                <input name="reason" value="{{ old('reason') }}" required maxlength="2000" placeholder="Ex : rectification admin apres validation" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>

            <x-button type="submit" tone="secondary" icon="refresh-cw">Changer</x-button>
        </form>
    </x-card>
@endif
