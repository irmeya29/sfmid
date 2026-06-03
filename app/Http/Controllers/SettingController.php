<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementUnitRequest;
use App\Http\Requests\StorePaymentModeRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\CompanySetting;
use App\Models\DocumentNumberSequence;
use App\Models\MeasurementUnit;
use App\Models\PaymentMode;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'settings' => CompanySetting::query()->pluck('value', 'key')->all(),
            'sequences' => DocumentNumberSequence::query()->orderBy('document_type')->get(),
            'paymentModes' => PaymentMode::query()->orderBy('name')->get(),
            'measurementUnits' => MeasurementUnit::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateSettingRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $oldValues = CompanySetting::query()->pluck('value', 'key')->all();

        DB::transaction(function () use ($request, $data): void {
            foreach (['company', 'sales', 'stock', 'pdf'] as $group) {
                foreach (($data[$group] ?? []) as $key => $value) {
                    if (in_array($key, ['logo', 'header_image', 'footer_image'], true)) {
                        continue;
                    }

                    CompanySetting::query()->updateOrCreate(
                        ['key' => "{$group}.{$key}"],
                        [
                            'value' => is_bool($value) ? (string) (int) $value : (string) $value,
                            'group' => $group,
                            'type' => $this->typeFor("{$group}.{$key}"),
                            'is_public' => in_array($group, ['company', 'pdf'], true) || "{$group}.{$key}" === 'sales.currency',
                        ]
                    );
                }
            }

            foreach (['stock.reserve_on_proforma', 'stock.allow_negative_stock', 'stock.low_stock_alert_enabled'] as $key) {
                CompanySetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->boolean(str_replace('.', '.', $key)) ? '1' : '0', 'group' => 'stock', 'type' => 'boolean', 'is_public' => false]
                );
            }

            if ($request->hasFile('company.logo')) {
                $file = $request->file('company.logo');
                $filename = 'sfmid-logo-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();
                if (! is_dir(public_path('uploads/settings'))) {
                    mkdir(public_path('uploads/settings'), 0755, true);
                }
                $file->move(public_path('uploads/settings'), $filename);

                CompanySetting::query()->updateOrCreate(
                    ['key' => 'company.logo_path'],
                    ['value' => 'uploads/settings/'.$filename, 'group' => 'company', 'type' => 'string', 'is_public' => true]
                );
            }

            foreach (['header_image' => 'pdf-header', 'footer_image' => 'pdf-footer'] as $input => $prefix) {
                if (! $request->hasFile("pdf.{$input}")) {
                    continue;
                }

                $file = $request->file("pdf.{$input}");
                $filename = $prefix.'-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();

                if (! is_dir(public_path('uploads/settings'))) {
                    mkdir(public_path('uploads/settings'), 0755, true);
                }

                $file->move(public_path('uploads/settings'), $filename);

                CompanySetting::query()->updateOrCreate(
                    ['key' => "pdf.{$input}_path"],
                    ['value' => 'uploads/settings/'.$filename, 'group' => 'pdf', 'type' => 'string', 'is_public' => true]
                );
            }

            foreach (($data['sequences'] ?? []) as $id => $sequenceData) {
                DocumentNumberSequence::query()->whereKey($id)->update([
                    'prefix' => Str::upper($sequenceData['prefix']),
                    'next_number' => $sequenceData['next_number'],
                    'padding' => $sequenceData['padding'],
                    'reset_period' => $sequenceData['reset_period'] ?: null,
                ]);
            }
        });

        $logger->log('updated', 'settings', 'Parametres generaux mis a jour.', oldValues: $oldValues, newValues: CompanySetting::query()->pluck('value', 'key')->all());

        return back()->with('success', 'Parametres enregistres.');
    }

    public function storePaymentMode(StorePaymentModeRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $mode = PaymentMode::query()->create([
            'name' => $request->validated('name'),
            'code' => Str::slug($request->validated('code'), '_'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $logger->log('created', 'settings', "Mode de paiement {$mode->code} cree.", $mode, newValues: $mode->toArray());

        return back()->with('success', 'Mode de paiement ajoute.');
    }

    public function togglePaymentMode(PaymentMode $paymentMode, ActivityLogger $logger): RedirectResponse
    {
        $old = $paymentMode->is_active;
        $paymentMode->forceFill(['is_active' => ! $paymentMode->is_active])->save();
        $logger->log('updated', 'settings', "Mode de paiement {$paymentMode->code} modifie.", $paymentMode, ['is_active' => $old], ['is_active' => $paymentMode->is_active]);

        return back()->with('success', 'Mode de paiement mis a jour.');
    }

    public function storeMeasurementUnit(StoreMeasurementUnitRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $unit = MeasurementUnit::query()->create([
            'name' => $request->validated('name'),
            'code' => Str::slug($request->validated('code'), '_'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $logger->log('created', 'settings', "Unite {$unit->code} creee.", $unit, newValues: $unit->toArray());

        return back()->with('success', 'Unite de mesure ajoutee.');
    }

    public function toggleMeasurementUnit(MeasurementUnit $measurementUnit, ActivityLogger $logger): RedirectResponse
    {
        $old = $measurementUnit->is_active;
        $measurementUnit->forceFill(['is_active' => ! $measurementUnit->is_active])->save();
        $logger->log('updated', 'settings', "Unite {$measurementUnit->code} modifiee.", $measurementUnit, ['is_active' => $old], ['is_active' => $measurementUnit->is_active]);

        return back()->with('success', 'Unite de mesure mise a jour.');
    }

    private function typeFor(string $key): string
    {
        return match ($key) {
            'sales.default_tax_rate' => 'decimal',
            'sales.default_payment_delay_days' => 'integer',
            'stock.reserve_on_proforma', 'stock.allow_negative_stock', 'stock.low_stock_alert_enabled' => 'boolean',
            default => 'string',
        };
    }
}
