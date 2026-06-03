<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</label>
            <select name="expense_category_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Sélectionner</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense->expense_category_id) === (string) $category->id)>
                        {{ $category->name }} @if($category->is_sensitive) - sensible @endif
                    </option>
                @endforeach
            </select>
            @error('expense_category_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Montant</label>
            <input type="number" min="1" step="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Date dépense</label>
            <input type="date" name="expense_date" value="{{ old('expense_date', optional($expense->expense_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @error('expense_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Bénéficiaire</label>
            <input name="beneficiary" value="{{ old('beneficiary', $expense->beneficiary) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Mode paiement</label>
            <select name="payment_method" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @foreach(['cash' => 'Espèces', 'bank_transfer' => 'Virement', 'check' => 'Chèque', 'mobile_money' => 'Mobile Money', 'other' => 'Autre'] as $code => $label)
                    <option value="{{ $code }}" @selected(old('payment_method', $expense->payment_method ?? 'cash') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Référence paiement</label>
            <input name="payment_reference" value="{{ old('payment_reference', $expense->payment_reference) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Justificatif</label>
            <input type="file" name="attachment" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            @if($expense->attachment_path)
                <p class="mt-2 text-sm text-slate-500">Justificatif actuel : <a href="{{ asset('storage/'.$expense->attachment_path) }}" target="_blank" class="font-semibold">ouvrir</a></p>
            @endif
            @error('attachment')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
            <textarea name="description" rows="4" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('description', $expense->description) }}</textarea>
            @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        <x-button type="submit" icon="save">{{ $submitLabel }}</x-button>
        <a href="{{ route('expenses.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Annuler</a>
    </div>
</form>
