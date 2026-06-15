<?php

namespace App\Http\Controllers;

use App\Models\StockSite;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockSiteController extends Controller
{
    public function index(): View
    {
        $sites = StockSite::query()
            ->withCount('productStocks')
            ->withSum('productStocks as physical_stock_total', 'physical_stock')
            ->withSum('productStocks as suspense_stock_total', 'suspense_stock')
            ->withSum('productStocks as tool_stock_total', 'tool_stock')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('stock-sites.index', [
            'sites' => $sites,
            'site' => new StockSite([
                'can_store' => true,
                'can_sell' => false,
                'is_active' => true,
                'is_default' => false,
            ]),
            'action' => route('stock-sites.store'),
            'method' => 'POST',
            'submitLabel' => 'Creer le site',
        ]);
    }

    public function edit(StockSite $stockSite): View
    {
        $sites = StockSite::query()
            ->withCount('productStocks')
            ->withSum('productStocks as physical_stock_total', 'physical_stock')
            ->withSum('productStocks as suspense_stock_total', 'suspense_stock')
            ->withSum('productStocks as tool_stock_total', 'tool_stock')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('stock-sites.index', [
            'sites' => $sites,
            'site' => $stockSite,
            'action' => route('stock-sites.update', $stockSite),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer',
        ]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request);

        $site = DB::transaction(function () use ($data): StockSite {
            $data['code'] = $data['code'] ?: Str::slug($data['name'], '-');
            $data['code'] = Str::upper($data['code']);
            $data['can_store'] = (bool) ($data['can_store'] ?? false);
            $data['can_sell'] = (bool) ($data['can_sell'] ?? false);
            $data['is_default'] = (bool) ($data['is_default'] ?? false);
            $data['is_active'] = (bool) ($data['is_active'] ?? false);

            $this->normalizeCapabilities($data);

            if ($data['is_default']) {
                StockSite::query()->update(['is_default' => false]);
            }

            return StockSite::query()->create($data);
        });

        $logger->log('created', 'stock', "Site de stock {$site->name} cree.", $site, newValues: $site->toArray());

        return redirect()->route('stock-sites.index')->with('success', 'Site ajoute.');
    }

    public function update(Request $request, StockSite $stockSite, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request, $stockSite);
        $oldValues = $stockSite->toArray();

        DB::transaction(function () use ($stockSite, $data): void {
            $data['code'] = Str::upper($data['code'] ?: Str::slug($data['name'], '-'));
            $data['can_store'] = (bool) ($data['can_store'] ?? false);
            $data['can_sell'] = (bool) ($data['can_sell'] ?? false);
            $data['is_default'] = (bool) ($data['is_default'] ?? false);
            $data['is_active'] = (bool) ($data['is_active'] ?? false);

            $this->normalizeCapabilities($data);
            $this->guardDefaultSite($stockSite, $data);
            $this->guardLastSalesSite($stockSite, $data);

            if ($data['is_default']) {
                StockSite::query()->whereKeyNot($stockSite->id)->update(['is_default' => false]);
            }

            $stockSite->update($data);
        });

        $logger->log(
            'updated',
            'stock',
            "Site de stock {$stockSite->name} modifie.",
            $stockSite,
            oldValues: $oldValues,
            newValues: $stockSite->fresh()->toArray()
        );

        return redirect()->route('stock-sites.index')->with('success', 'Site mis a jour.');
    }

    public function toggle(StockSite $stockSite, ActivityLogger $logger): RedirectResponse
    {
        DB::transaction(function () use ($stockSite): void {
            $newState = ! $stockSite->is_active;

            if (! $newState) {
                $this->guardDefaultSite($stockSite, [
                    'is_default' => $stockSite->is_default,
                    'is_active' => false,
                    'can_store' => $stockSite->can_store,
                    'can_sell' => $stockSite->can_sell,
                ]);

                $this->guardLastSalesSite($stockSite, [
                    'is_active' => false,
                    'can_sell' => $stockSite->can_sell,
                ]);
            }

            $stockSite->forceFill(['is_active' => $newState])->save();
        });

        $logger->log('updated', 'stock', "Activation site {$stockSite->name} modifiee.", $stockSite, newValues: [
            'is_active' => $stockSite->fresh()->is_active,
        ]);

        return back()->with('success', 'Statut du site mis a jour.');
    }

    public function makeDefault(StockSite $stockSite, ActivityLogger $logger): RedirectResponse
    {
        if (! $stockSite->is_active || ! $stockSite->can_store) {
            throw ValidationException::withMessages([
                'is_default' => 'Le site par defaut doit etre actif et autorise au stockage.',
            ]);
        }

        DB::transaction(function () use ($stockSite): void {
            StockSite::query()->whereKeyNot($stockSite->id)->update(['is_default' => false]);
            $stockSite->forceFill(['is_default' => true])->save();
        });

        $logger->log('updated', 'stock', "Site {$stockSite->name} defini comme site par defaut.", $stockSite);

        return back()->with('success', 'Site par defaut mis a jour.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?StockSite $site = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('stock_sites', 'code')->ignore($site),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'can_store' => ['nullable', 'boolean'],
            'can_sell' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeCapabilities(array &$data): void
    {
        if ($data['can_sell']) {
            $data['can_store'] = true;
            $data['is_active'] = true;
        }

        if ($data['is_default']) {
            $data['can_store'] = true;
            $data['is_active'] = true;
        }

        if (! $data['can_store'] && ! $data['can_sell']) {
            throw ValidationException::withMessages([
                'can_store' => 'Un site doit au moins stocker ou vendre.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardDefaultSite(StockSite $site, array $data): void
    {
        if (! $site->is_default) {
            return;
        }

        if (! $data['is_default'] || ! $data['is_active'] || ! $data['can_store']) {
            throw ValidationException::withMessages([
                'is_default' => 'Le site par defaut doit rester actif et autorise au stockage.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardLastSalesSite(StockSite $site, array $data): void
    {
        $willRemainSales = (bool) ($data['is_active'] ?? $site->is_active)
            && (bool) ($data['can_sell'] ?? $site->can_sell);

        if ($willRemainSales || ! $site->is_active || ! $site->can_sell) {
            return;
        }

        $otherSalesSites = StockSite::query()
            ->whereKeyNot($site->id)
            ->where('is_active', true)
            ->where('can_sell', true)
            ->exists();

        if (! $otherSalesSites) {
            throw ValidationException::withMessages([
                'can_sell' => 'Il faut garder au moins un site de vente actif.',
            ]);
        }
    }
}
