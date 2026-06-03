<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withCount(['products', 'purchaseOrders', 'invoices'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')->orWhere('code', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', ['suppliers' => $suppliers, 'filters' => ['search' => $request->string('search')->toString()]]);
    }

    public function create(): View
    {
        return view('suppliers.create', ['supplier' => new Supplier(['is_active' => true]), 'products' => Product::query()->active()->orderBy('name')->get()]);
    }

    public function store(StoreSupplierRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $supplier = Supplier::query()->create([
            ...collect($data)->except('product_ids')->all(),
            'code' => ($data['code'] ?? null) ?: $this->generateCode(),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_by' => $request->user()->id,
        ]);
        $supplier->products()->sync($data['product_ids'] ?? []);
        $logger->log('created', 'suppliers', "Fournisseur {$supplier->code} créé.", $supplier, newValues: $supplier->toArray());

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur créé.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['products', 'purchaseOrders.items', 'invoices.payments']);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        $supplier->load('products');

        return view('suppliers.edit', ['supplier' => $supplier, 'products' => Product::query()->active()->orderBy('name')->get()]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $old = $supplier->toArray();
        $supplier->forceFill([ ...collect($data)->except('product_ids')->all(), 'is_active' => (bool) ($data['is_active'] ?? false) ])->save();
        $supplier->products()->sync($data['product_ids'] ?? []);
        $logger->log('updated', 'suppliers', "Fournisseur {$supplier->code} modifié.", $supplier, $old, $supplier->fresh()->toArray());

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur modifié.');
    }

    public function destroy(Supplier $supplier, ActivityLogger $logger): RedirectResponse
    {
        DB::transaction(function () use ($supplier, $logger): void {
            $logger->log('deleted', 'suppliers', "Fournisseur {$supplier->code} supprimé.", $supplier, $supplier->toArray());
            $supplier->delete();
        });

        return redirect()->route('suppliers.index')->with('success', 'Fournisseur supprimé.');
    }

    private function generateCode(): string
    {
        $next = (Supplier::withTrashed()->max('id') ?? 0) + 1;
        do {
            $code = 'FOU-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Supplier::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
