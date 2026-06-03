<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Enums\ProductStockKind;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Client;
use App\Models\ClientProductPrice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $products = Product::query()
            ->with('category')
            ->search($request->string('search')->toString())
            ->when($request->filled('category'), fn ($query) => $query->where('product_category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('stock_kind'), fn ($query) => $query->where('stock_kind', $request->string('stock_kind')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => ProductCategory::query()->active()->orderBy('name')->get(),
            'statuses' => ProductStatus::options(),
            'stockKinds' => ProductStockKind::options(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category' => $request->string('category')->toString(),
                'status' => $request->string('status')->toString(),
                'stock_kind' => $request->string('stock_kind')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create', [
            'product' => new Product([
                'status' => ProductStatus::Active,
                'stock_kind' => ProductStockKind::Commercial,
                'unit' => 'piece',
                'physical_stock' => 0,
                'reserved_stock' => 0,
                'suspense_stock' => 0,
                'tool_stock' => 0,
                'alert_threshold' => 0,
            ]),
            'categories' => ProductCategory::query()->active()->orderBy('name')->get(),
            'statuses' => ProductStatus::options(),
            'stockKinds' => ProductStockKind::options(),
        ]);
    }

    public function store(StoreProductRequest $request, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $product = DB::transaction(function () use ($request, $activityLogger): Product {
            $data = $request->validated();

            $product = Product::query()->create([
                ...$data,
                'code' => $data['code'] ?: $this->generateProductCode(),
                'reserved_stock' => 0,
                'suspense_stock' => 0,
                'created_by' => $request->user()->id,
            ]);

            $activityLogger->log(
                action: 'created',
                module: 'products',
                description: "Produit {$product->code} créé.",
                subject: $product,
                newValues: $product->only([
                    'code',
                    'name',
                    'internal_reference',
                    'product_category_id',
                    'description',
                    'unit',
                    'purchase_price',
                    'sale_price',
                    'physical_stock',
                    'alert_threshold',
                    'stock_kind',
                    'status',
                ]),
            );

            return $product;
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        $product->load([
            'category',
            'clientPrices.client',
            'stockMovements' => fn ($query) => $query->latest()->limit(20),
        ]);

        return view('products.show', [
            'product' => $product,
            'clients' => Client::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'categories' => ProductCategory::query()->active()->orderBy('name')->get(),
            'statuses' => ProductStatus::options(),
            'stockKinds' => ProductStockKind::options(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('update', $product);

        DB::transaction(function () use ($request, $product, $activityLogger): void {
            $oldValues = $product->only([
                'product_category_id',
                'code',
                'name',
                'brand',
                'internal_reference',
                'supplier_reference',
                'description',
                'unit',
                'purchase_price',
                'sale_price',
                'physical_stock',
                'tool_stock',
                'alert_threshold',
                'stock_kind',
                'status',
            ]);

            $product->update($request->validated());

            $activityLogger->log(
                action: 'updated',
                module: 'products',
                description: "Produit {$product->code} modifié.",
                subject: $product,
                oldValues: $oldValues,
                newValues: $product->fresh()->only(array_keys($oldValues)),
            );
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produit modifié avec succès.');
    }

    public function destroy(Product $product, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('delete', $product);

        if ($product->proformaItems()->exists() || $product->deliveryNoteItems()->exists() || $product->invoiceItems()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce produit car il est déjà utilisé dans des documents.');
        }

        DB::transaction(function () use ($product, $activityLogger): void {
            $activityLogger->log(
                action: 'deleted',
                module: 'products',
                description: "Produit {$product->code} supprimé.",
                subject: $product,
                oldValues: $product->only(['code', 'name', 'status']),
            );

            $product->delete();
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }

    public function storeClientReference(Request $request, Product $product, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('update', $product);

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'client_reference' => ['required', 'string', 'max:255'],
            'client_designation' => ['nullable', 'string', 'max:255'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $reference = ClientProductPrice::query()->updateOrCreate(
            [
                'client_id' => $data['client_id'],
                'product_id' => $product->id,
            ],
            [
                'client_reference' => $data['client_reference'],
                'client_designation' => $data['client_designation'] ?? null,
                'sale_price' => $data['sale_price'] ?? $product->sale_price,
                'discount_rate' => $data['discount_rate'] ?? 0,
                'created_by' => $request->user()->id,
            ]
        );

        $activityLogger->log(
            action: 'upserted_client_reference',
            module: 'products',
            description: "Reference client ajoutee sur {$product->code}.",
            subject: $product,
            newValues: $reference->toArray(),
        );

        return back()->with('success', 'Reference client/mine enregistree.');
    }

    public function destroyClientReference(Product $product, ClientProductPrice $clientProductPrice, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('update', $product);

        if ($clientProductPrice->product_id !== $product->id) {
            abort(404);
        }

        $oldValues = $clientProductPrice->toArray();
        $clientProductPrice->delete();

        $activityLogger->log(
            action: 'deleted_client_reference',
            module: 'products',
            description: "Reference client supprimee sur {$product->code}.",
            subject: $product,
            oldValues: $oldValues,
        );

        return back()->with('success', 'Reference client/mine supprimee.');
    }

    private function generateProductCode(): string
    {
        do {
            $code = 'PRD-'.now()->format('ym').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Product::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
