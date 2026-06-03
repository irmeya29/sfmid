<form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-4 lg:grid-cols-5">
        <select name="type" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Tous types</option>
            @foreach($types as $key => $label)
                <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="creator_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Tous utilisateurs</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected($filters['creator_id'] === $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
        <x-button type="submit" icon="filter">Filtrer</x-button>
    </div>
</form>
