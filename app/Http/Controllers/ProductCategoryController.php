<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->withCount('products')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('product-categories.index', [
            'categories' => $categories,
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ProductCategory::class);

        return view('product-categories.create', [
            'category' => new ProductCategory([
                'is_active' => true,
            ]),
            'parents' => ProductCategory::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductCategoryRequest $request, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('create', ProductCategory::class);

        $category = DB::transaction(function () use ($request, $activityLogger): ProductCategory {
            $data = $request->validated();

            $category = ProductCategory::query()->create([
                ...$data,
                'slug' => Str::slug($data['name']),
            ]);

            $activityLogger->log(
                action: 'created',
                module: 'product_categories',
                description: "Catégorie produit {$category->name} créée.",
                subject: $category,
                newValues: $category->only(['name', 'slug', 'is_active']),
            );

            return $category;
        });

        return redirect()
            ->route('product-categories.index')
            ->with('success', "Catégorie {$category->name} créée avec succès.");
    }

    public function edit(ProductCategory $productCategory): View
    {
        Gate::authorize('update', $productCategory);

        return view('product-categories.edit', [
            'category' => $productCategory,
            'parents' => ProductCategory::query()
                ->active()
                ->whereKeyNot($productCategory->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('update', $productCategory);

        DB::transaction(function () use ($request, $productCategory, $activityLogger): void {
            $oldValues = $productCategory->only(['parent_id', 'name', 'slug', 'description', 'is_active']);

            $data = $request->validated();

            $productCategory->update([
                ...$data,
                'slug' => Str::slug($data['name']),
            ]);

            $activityLogger->log(
                action: 'updated',
                module: 'product_categories',
                description: "Catégorie produit {$productCategory->name} modifiée.",
                subject: $productCategory,
                oldValues: $oldValues,
                newValues: $productCategory->fresh()->only(array_keys($oldValues)),
            );
        });

        return redirect()
            ->route('product-categories.index')
            ->with('success', 'Catégorie modifiée avec succès.');
    }

    public function destroy(ProductCategory $productCategory, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('delete', $productCategory);

        if ($productCategory->products()->exists()) {
            return back()->with('error', 'Impossible de supprimer une catégorie utilisée par des produits.');
        }

        DB::transaction(function () use ($productCategory, $activityLogger): void {
            $activityLogger->log(
                action: 'deleted',
                module: 'product_categories',
                description: "Catégorie produit {$productCategory->name} supprimée.",
                subject: $productCategory,
                oldValues: $productCategory->only(['name', 'slug', 'is_active']),
            );

            $productCategory->delete();
        });

        return redirect()
            ->route('product-categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}
