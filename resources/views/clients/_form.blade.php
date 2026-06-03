<form method="POST" action="{{ $action }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf

    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Code client</label>
            <input
                type="text"
                name="code"
                value="{{ old('code', $client->code) }}"
                placeholder="Automatique si vide"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Nom du client <span class="text-red-600">*</span></label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $client->name) }}"
                required
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Type client</label>
            <select name="type" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                @foreach($types as $type)
                    <option value="{{ $type['value'] }}" @selected(old('type', $client->type?->value) === $type['value'])>
                        {{ $type['label'] }}
                    </option>
                @endforeach
            </select>
            @error('type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
            <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10">
                @foreach($statuses as $status)
                    <option value="{{ $status['value'] }}" @selected(old('status', $client->status?->value) === $status['value'])>
                        {{ $status['label'] }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Téléphone</label>
            <input
                type="text"
                name="phone"
                value="{{ old('phone', $client->phone) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('phone')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email', $client->email) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">IFU</label>
            <input
                type="text"
                name="ifu"
                value="{{ old('ifu', $client->ifu) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('ifu')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">RCCM</label>
            <input
                type="text"
                name="rccm"
                value="{{ old('rccm', $client->rccm) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('rccm')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Délai de paiement en jours</label>
            <input
                type="number"
                name="payment_delay_days"
                value="{{ old('payment_delay_days', $client->payment_delay_days ?? 0) }}"
                min="0"
                max="365"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('payment_delay_days')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Adresse</label>
            <input
                type="text"
                name="address"
                value="{{ old('address', $client->address) }}"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >
            @error('address')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Conditions commerciales</label>
            <textarea
                name="commercial_terms"
                rows="4"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            >{{ old('commercial_terms', $client->commercial_terms) }}</textarea>
            @error('commercial_terms')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit" icon="save">{{ $submitLabel }}</x-button>

        <a href="{{ route('clients.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Annuler
        </a>
    </div>
</form>
