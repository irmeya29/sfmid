<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('expense-categories.index', ['categories' => $categories, 'filters' => ['search' => $request->string('search')->toString()]]);
    }

    public function create(): View
    {
        return view('expense-categories.create', ['category' => new ExpenseCategory(['is_active' => true])]);
    }

    public function store(StoreExpenseCategoryRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $category = ExpenseCategory::query()->create([
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? null) ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $logger->log('created', 'expense_categories', "Catégorie de charge {$category->name} créée.", $category, newValues: $category->toArray());

        return redirect()->route('expense-categories.index')->with('success', 'Catégorie de charge créée.');
    }

    public function show(ExpenseCategory $expenseCategory): View
    {
        $expenseCategory->load(['expenses' => fn ($query) => $query->latest()->limit(50)]);

        return view('expense-categories.show', ['category' => $expenseCategory]);
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        return view('expense-categories.edit', ['category' => $expenseCategory]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory, ActivityLogger $logger): RedirectResponse
    {
        $old = $expenseCategory->toArray();
        $data = $request->validated();
        $expenseCategory->forceFill([
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? null) ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        $logger->log('updated', 'expense_categories', "Catégorie de charge {$expenseCategory->name} modifiée.", $expenseCategory, $old, $expenseCategory->fresh()->toArray());

        return redirect()->route('expense-categories.index')->with('success', 'Catégorie de charge modifiée.');
    }

    public function destroy(ExpenseCategory $expenseCategory, ActivityLogger $logger): RedirectResponse
    {
        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', 'Suppression impossible : la catégorie est déjà utilisée.');
        }

        $logger->log('deleted', 'expense_categories', "Catégorie de charge {$expenseCategory->name} supprimée.", $expenseCategory, $expenseCategory->toArray());
        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')->with('success', 'Catégorie de charge supprimée.');
    }
}
