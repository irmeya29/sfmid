<?php

namespace App\Http\Controllers;

use App\Actions\Stock\SaveStockMovementAction;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\StockSite;
use App\Models\StockMovement;
use App\Models\StockSuspense;
use App\Services\Stock\StockSiteInventory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StockController extends Controller
{
    public function physical(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        return view('stock.products', [
            'title' => 'Stock physique',
            'subtitle' => 'Quantités disponibles en magasin',
            'products' => $this->products($request)->paginate(15)->withQueryString(),
            'stockColumn' => 'physical_stock',
            'stockSites' => StockSite::query()->active()->orderBy('name')->get(),
            'filters' => $this->stockProductFilters($request),
        ]);
    }

    public function reserved(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        return view('stock.products', [
            'title' => 'Stock réservé',
            'subtitle' => 'Quantités bloquées pour opérations non livrées',
            'products' => $this->products($request)->where('reserved_stock', '>', 0)->paginate(15)->withQueryString(),
            'stockColumn' => 'reserved_stock',
            'stockSites' => StockSite::query()->active()->orderBy('name')->get(),
            'filters' => $this->stockProductFilters($request),
        ]);
    }

    public function tool(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        return view('stock.products', [
            'title' => 'Stock outil',
            'subtitle' => 'Matériel interne non vendable',
            'products' => $this->products($request)->where('tool_stock', '>', 0)->paginate(15)->withQueryString(),
            'stockColumn' => 'tool_stock',
            'stockSites' => StockSite::query()->active()->orderBy('name')->get(),
            'filters' => $this->stockProductFilters($request),
        ]);
    }

    public function suspense(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        $suspenses = StockSuspense::query()
            ->with(['client', 'product', 'stockSite', 'deliveryNote', 'invoice'])
            ->open()
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->integer('client_id')))
            ->when($request->filled('stock_site_id'), fn ($query) => $query->where('stock_site_id', $request->integer('stock_site_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('stock.suspense', [
            'suspenses' => $suspenses,
            'clients' => Client::query()->orderBy('name')->get(),
            'stockSites' => StockSite::query()->active()->orderBy('name')->get(),
            'filters' => [
                'client_id' => $request->integer('client_id') ?: null,
                'stock_site_id' => $request->integer('stock_site_id') ?: null,
            ],
        ]);
    }

    public function movements(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        $movements = StockMovement::query()
            ->with(['product', 'stockSite', 'destinationStockSite', 'creator', 'validator'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('stock_site_id'), fn ($query) => $query->where('stock_site_id', $request->integer('stock_site_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('stock.movements', [
            'movements' => $movements,
            'statuses' => StockMovementStatus::options(),
            'types' => StockMovementType::options(),
            'stockSites' => StockSite::query()->active()->orderBy('name')->get(),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'type' => $request->string('type')->toString(),
                'stock_site_id' => $request->integer('stock_site_id') ?: null,
            ],
        ]);
    }

    public function createEntry(): View
    {
        Gate::authorize('createEntry', StockMovement::class);

        return view('stock.create-movement', [
            'title' => 'Entrée stock fournisseur',
            'action' => route('stock.movements.store'),
            'products' => Product::query()->active()->orderBy('name')->get(),
            'stockSites' => StockSite::query()->active()->where('can_store', true)->orderBy('name')->get(),
            'defaultStockSiteId' => app(StockSiteInventory::class)->defaultSite()->id,
            'types' => [
                StockMovementType::PurchaseEntry,
                StockMovementType::CustomerReturn,
            ],
            'defaultType' => StockMovementType::PurchaseEntry->value,
            'defaultStockColumn' => 'physical_stock',
        ]);
    }

    public function createExit(): View
    {
        Gate::authorize('createExit', StockMovement::class);

        return view('stock.create-movement', [
            'title' => 'Sortie stock manuelle',
            'action' => route('stock.movements.store'),
            'products' => Product::query()->active()->orderBy('name')->get(),
            'stockSites' => StockSite::query()->active()->where('can_store', true)->orderBy('name')->get(),
            'defaultStockSiteId' => app(StockSiteInventory::class)->defaultSite()->id,
            'types' => [
                StockMovementType::InternalUse,
                StockMovementType::LossOrDamage,
            ],
            'defaultType' => StockMovementType::InternalUse->value,
            'defaultStockColumn' => 'physical_stock',
        ]);
    }

    public function createAdjustment(): View
    {
        Gate::authorize('adjust', StockMovement::class);

        return view('stock.create-movement', [
            'title' => 'Ajustement stock',
            'action' => route('stock.movements.store'),
            'products' => Product::query()->active()->orderBy('name')->get(),
            'stockSites' => StockSite::query()->active()->where('can_store', true)->orderBy('name')->get(),
            'defaultStockSiteId' => app(StockSiteInventory::class)->defaultSite()->id,
            'types' => [
                StockMovementType::PositiveAdjustment,
                StockMovementType::NegativeAdjustment,
            ],
            'defaultType' => StockMovementType::PositiveAdjustment->value,
            'defaultStockColumn' => 'physical_stock',
        ]);
    }

    public function createTransfer(): View
    {
        Gate::authorize('createExit', StockMovement::class);

        return view('stock.transfer', [
            'products' => Product::query()->active()->orderBy('name')->get(),
            'stockSites' => StockSite::query()->active()->where('can_store', true)->orderBy('name')->get(),
            'defaultStockSiteId' => app(StockSiteInventory::class)->defaultSite()->id,
        ]);
    }

    public function storeTransfer(Request $request, SaveStockMovementAction $action): RedirectResponse
    {
        Gate::authorize('createExit', StockMovement::class);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'from_stock_site_id' => ['required', 'integer', Rule::exists('stock_sites', 'id')->where('is_active', true)->where('can_store', true)],
            'to_stock_site_id' => ['required', 'integer', 'different:from_stock_site_id', Rule::exists('stock_sites', 'id')->where('is_active', true)->where('can_store', true)],
            'stock_column' => ['required', Rule::in(['physical_stock', 'tool_stock'])],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->transfer($data, $request->user());

        return redirect()->route('stock.movements')->with('success', 'Transfert stock enregistre.');
    }

    public function store(StoreStockMovementRequest $request, SaveStockMovementAction $action): RedirectResponse
    {
        $type = StockMovementType::from($request->validated('type'));

        if (in_array($type, [StockMovementType::PurchaseEntry, StockMovementType::CustomerReturn], true)) {
            Gate::authorize('createEntry', StockMovement::class);
        } elseif (in_array($type, [StockMovementType::PositiveAdjustment, StockMovementType::NegativeAdjustment], true)) {
            Gate::authorize('adjust', StockMovement::class);
        } else {
            Gate::authorize('createExit', StockMovement::class);
        }

        $movement = $action->execute($request->validated(), $request->user());

        $message = $movement->status === StockMovementStatus::PendingValidation
            ? 'Mouvement soumis pour validation.'
            : 'Mouvement stock enregistré et appliqué.';

        return redirect()->route('stock.movements')->with('success', $message);
    }

    public function validateMovement(StockMovement $stockMovement, Request $request, SaveStockMovementAction $action): RedirectResponse
    {
        Gate::authorize('validate', $stockMovement);

        $action->applyPending($stockMovement, $request->user());

        return redirect()->route('stock.movements')->with('success', 'Mouvement stock validé.');
    }

    public function lowStock(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        return view('stock.low-stock', [
            'products' => Product::query()->with('category')->lowStock()->orderBy('name')->paginate(20)->withQueryString(),
        ]);
    }

    public function reportPdf(Request $request): Response
    {
        Gate::authorize('export', StockMovement::class);

        $report = $request->string('report')->toString() ?: 'physical';
        $company = CompanySetting::query()->pluck('value', 'key')->all();

        $data = match ($report) {
            'low_stock' => ['rows' => Product::query()->with('category')->lowStock()->orderBy('name')->get(), 'title' => 'Rapport stock bas'],
            'suspense' => ['rows' => StockSuspense::query()->with(['client', 'product', 'invoice'])->open()->latest()->get(), 'title' => 'Rapport stock en suspens'],
            default => ['rows' => Product::query()->with('category')->orderBy('name')->get(), 'title' => 'Rapport stock physique'],
        };

        return Pdf::loadView('stock.report-pdf', [
            'company' => $company,
            'report' => $report,
            ...$data,
        ])->setPaper('a4')->stream(str($data['title'])->slug().'.pdf');
    }

    private function products(Request $request)
    {
        $stockSiteId = $request->integer('stock_site_id') ?: null;

        return Product::query()
            ->with([
                'category',
                'stockSiteStocks' => fn ($query) => $query->when($stockSiteId, fn ($query) => $query->where('stock_site_id', $stockSiteId)),
            ])
            ->search($request->string('search')->toString())
            ->orderBy('name');
    }

    /**
     * @return array{search: string, stock_site_id: int|null}
     */
    private function stockProductFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'stock_site_id' => $request->integer('stock_site_id') ?: null,
        ];
    }
}
