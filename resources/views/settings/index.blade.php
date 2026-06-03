@extends('layouts.app')

@section('title', 'Parametres | SFMID Gestion')
@section('subtitle', 'Configuration')
@section('page-title', 'Parametres')

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Informations societe</h3>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <input name="company[name]" value="{{ old('company.name', $settings['company.name'] ?? 'SFMID') }}" placeholder="Nom commercial" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
                <input name="company[full_name]" value="{{ old('company.full_name', $settings['company.full_name'] ?? '') }}" placeholder="Raison sociale" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="company[phone]" value="{{ old('company.phone', $settings['company.phone'] ?? '') }}" placeholder="Telephone" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="company[email]" value="{{ old('company.email', $settings['company.email'] ?? '') }}" placeholder="Email" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="company[ifu]" value="{{ old('company.ifu', $settings['company.ifu'] ?? '') }}" placeholder="IFU" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="company[rccm]" value="{{ old('company.rccm', $settings['company.rccm'] ?? '') }}" placeholder="RCCM" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <textarea name="company[address]" rows="3" placeholder="Adresse" class="rounded-xl border border-slate-300 px-4 py-3 text-sm lg:col-span-2">{{ old('company.address', $settings['company.address'] ?? '') }}</textarea>
                <div>
                    <input type="file" name="company[logo]" accept="image/*" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @if(! empty($settings['company.logo_path']))
                        <p class="mt-2 text-xs text-slate-500">Logo actuel : {{ $settings['company.logo_path'] }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Ventes, devise et stock</h3>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <input name="sales[currency]" value="{{ old('sales.currency', $settings['sales.currency'] ?? 'FCFA') }}" placeholder="Devise" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" required>
                <input type="number" name="sales[default_payment_delay_days]" value="{{ old('sales.default_payment_delay_days', $settings['sales.default_payment_delay_days'] ?? 0) }}" placeholder="Delai paiement defaut" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" min="0">
                <input type="number" step="0.01" name="sales[default_tax_rate]" value="{{ old('sales.default_tax_rate', $settings['sales.default_tax_rate'] ?? 0) }}" placeholder="Taux taxe defaut" class="rounded-xl border border-slate-300 px-4 py-3 text-sm" min="0">
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-semibold"><input type="hidden" name="stock[reserve_on_proforma]" value="0"><input type="checkbox" name="stock[reserve_on_proforma]" value="1" @checked(($settings['stock.reserve_on_proforma'] ?? '0') === '1')> Reserver sur proforma</label>
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-semibold"><input type="hidden" name="stock[allow_negative_stock]" value="0"><input type="checkbox" name="stock[allow_negative_stock]" value="1" @checked(($settings['stock.allow_negative_stock'] ?? '0') === '1')> Autoriser stock negatif</label>
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-semibold"><input type="hidden" name="stock[low_stock_alert_enabled]" value="0"><input type="checkbox" name="stock[low_stock_alert_enabled]" value="1" @checked(($settings['stock.low_stock_alert_enabled'] ?? '1') === '1')> Alertes stock bas</label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Presentation PDF</h3>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <input name="pdf[signature_left]" value="{{ old('pdf.signature_left', $settings['pdf.signature_left'] ?? 'SFMID') }}" placeholder="Signature gauche" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="pdf[signature_right]" value="{{ old('pdf.signature_right', $settings['pdf.signature_right'] ?? 'Client') }}" placeholder="Signature droite" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <textarea name="pdf[footer_note]" rows="2" placeholder="Pied de page / note institutionnelle" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('pdf.footer_note', $settings['pdf.footer_note'] ?? '') }}</textarea>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Entete PDF</label>
                    <input type="file" name="pdf[header_image]" accept="image/*" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <p class="mt-2 text-xs text-slate-500">Actuel : {{ $settings['pdf.header_image_path'] ?? 'branding/pdf-header-print.jpg' }}</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Pied de page PDF</label>
                    <input type="file" name="pdf[footer_image]" accept="image/*" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <p class="mt-2 text-xs text-slate-500">Actuel : {{ $settings['pdf.footer_image_path'] ?? 'branding/pdf-footer-print.jpg' }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Numerotation documents</h3>
            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Document</th><th class="px-4 py-3 text-left">Prefixe</th><th class="px-4 py-3 text-left">Prochain numero</th><th class="px-4 py-3 text-left">Padding</th><th class="px-4 py-3 text-left">Reset</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sequences as $sequence)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $sequence->document_type }}</td>
                                <td class="px-4 py-3"><input name="sequences[{{ $sequence->id }}][prefix]" value="{{ $sequence->prefix }}" class="w-28 rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-4 py-3"><input type="number" name="sequences[{{ $sequence->id }}][next_number]" value="{{ $sequence->next_number }}" class="w-28 rounded-lg border border-slate-300 px-3 py-2" min="1"></td>
                                <td class="px-4 py-3"><input type="number" name="sequences[{{ $sequence->id }}][padding]" value="{{ $sequence->padding }}" class="w-24 rounded-lg border border-slate-300 px-3 py-2" min="2" max="10"></td>
                                <td class="px-4 py-3"><select name="sequences[{{ $sequence->id }}][reset_period]" class="rounded-lg border border-slate-300 px-3 py-2"><option value="">Aucun</option><option value="daily" @selected($sequence->reset_period === 'daily')>Journalier</option><option value="monthly" @selected($sequence->reset_period === 'monthly')>Mensuel</option><option value="yearly" @selected($sequence->reset_period === 'yearly')>Annuel</option></select></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">
            <x-button type="submit" icon="save">Enregistrer les parametres</x-button>
        </div>
    </form>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Modes de paiement</h3>
            <form method="POST" action="{{ route('settings.payment-modes.store') }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                @csrf
                <input name="name" placeholder="Nom" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="code" placeholder="Code" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <x-button type="submit" icon="plus">Ajouter</x-button>
            </form>
            <div class="mt-4 space-y-2">
                @foreach($paymentModes as $mode)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm"><span><strong>{{ $mode->name }}</strong> <span class="text-slate-500">{{ $mode->code }}</span></span><form method="POST" action="{{ route('settings.payment-modes.toggle', $mode) }}">@csrf<x-action-button type="submit" :icon="$mode->is_active ? 'toggle-right' : 'toggle-left'" :label="$mode->is_active ? 'Desactiver le mode' : 'Activer le mode'" :tone="$mode->is_active ? 'success' : 'danger'" /></form></div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Unites de mesure</h3>
            <form method="POST" action="{{ route('settings.measurement-units.store') }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                @csrf
                <input name="name" placeholder="Nom" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <input name="code" placeholder="Code" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <x-button type="submit" icon="plus">Ajouter</x-button>
            </form>
            <div class="mt-4 space-y-2">
                @foreach($measurementUnits as $unit)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm"><span><strong>{{ $unit->name }}</strong> <span class="text-slate-500">{{ $unit->code }}</span></span><form method="POST" action="{{ route('settings.measurement-units.toggle', $unit) }}">@csrf<x-action-button type="submit" :icon="$unit->is_active ? 'toggle-right' : 'toggle-left'" :label="$unit->is_active ? 'Desactiver l unite' : 'Activer l unite'" :tone="$unit->is_active ? 'success' : 'danger'" /></form></div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
