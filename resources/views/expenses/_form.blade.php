@php
    $attachmentPath = old('attachment_path', $expense->attachment_path);
    $attachmentUrl = $attachmentPath && $expense->exists ? route('expenses.attachment', $expense) : null;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Categorie <span class="text-red-600">*</span></label>
                <select name="expense_category_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Selectionner une categorie</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense->expense_category_id) === (string) $category->id)>
                            {{ $category->name }} @if($category->is_sensitive) - sensible @endif
                        </option>
                    @endforeach
                </select>
                @error('expense_category_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Montant <span class="text-red-600">*</span></label>
                <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-[#2676B3] focus-within:ring-2 focus-within:ring-[#2676B3]/10">
                    <input type="number" min="1" step="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" required class="min-w-0 flex-1 border-0 px-4 py-3 text-sm outline-none focus:ring-0">
                    <span class="flex items-center border-l border-slate-200 px-3 text-xs font-bold text-slate-500">FCFA</span>
                </div>
                @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Date depense <span class="text-red-600">*</span></label>
                <input type="date" name="expense_date" value="{{ old('expense_date', optional($expense->expense_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('expense_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Beneficiaire</label>
                <input name="beneficiary" value="{{ old('beneficiary', $expense->beneficiary) }}" placeholder="Ex : fournisseur, agent, prestataire" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('beneficiary')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Mode paiement</label>
                <select name="payment_method" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @foreach(['cash' => 'Especes', 'bank_transfer' => 'Virement', 'check' => 'Cheque', 'mobile_money' => 'Mobile Money', 'other' => 'Autre'] as $code => $label)
                        <option value="{{ $code }}" @selected(old('payment_method', $expense->payment_method ?? 'cash') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('payment_method')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Reference paiement</label>
                <input name="payment_reference" value="{{ old('payment_reference', $expense->payment_reference) }}" placeholder="Numero recu, transaction, cheque..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('payment_reference')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Description <span class="text-red-600">*</span></label>
                <textarea name="description" rows="5" required placeholder="Objet de la depense, contexte, details utiles..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('description', $expense->description) }}</textarea>
                @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <label class="block text-sm font-semibold text-slate-700">Justificatif</label>
            <label for="expense-attachment" class="mt-3 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-white px-4 py-8 text-center transition hover:border-[#2676B3] hover:bg-[#EAF4FB]">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#EAF4FB] text-[#2676B3]">
                    <i data-lucide="upload-cloud" class="h-6 w-6"></i>
                </span>
                <span class="mt-3 text-sm font-bold text-slate-800">Ajouter un justificatif</span>
                <span id="attachment-name" class="mt-1 max-w-full truncate text-xs text-slate-500">PDF ou image scannee, 10 Mo max</span>
                <input id="expense-attachment" type="file" name="attachment" accept="application/pdf,image/*,.tif,.tiff,.bmp" class="sr-only">
            </label>

            @if($attachmentUrl)
                <a href="{{ $attachmentUrl }}" target="_blank" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="file-search" class="h-4 w-4"></i>
                    Ouvrir le justificatif actuel
                </a>
            @endif
            @error('attachment')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
        </aside>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
        <x-button type="submit" icon="save">{{ $submitLabel }}</x-button>
        <a href="{{ route('expenses.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Annuler</a>
    </div>
</form>

<script>
    document.getElementById('expense-attachment')?.addEventListener('change', event => {
        const file = event.target.files?.[0];
        const label = document.getElementById('attachment-name');
        if (file && label) label.textContent = file.name;
    });
</script>
