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
use App\Services\Stock\StockSiteInventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use SplFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    private const CSV_HEADERS = [
        'code',
        'name',
        'category_id',
        'category_name',
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
        'client_name',
        'client_reference',
        'client_designation',
        'client_sale_price',
    ];

    private const CSV_TEMPLATE_HEADERS = [
        'Code produit',
        'Nom du produit',
        'Categorie',
        'Marque',
        'Reference interne SFMID',
        'Reference fournisseur',
        'Description',
        'Unite',
        'Prix achat',
        'Prix vente',
        'Stock physique initial',
        'Stock outil',
        'Seuil alerte',
        'Type de stock',
        'Statut',
        'Client / Mine',
        'Reference client',
        'Appellation client',
        'Prix client',
    ];

    private const CSV_HEADER_ALIASES = [
        'code produit' => 'code',
        'nom du produit' => 'name',
        'category id' => 'category_id',
        'category name' => 'category_name',
        'categorie id' => 'category_id',
        'categorie' => 'category_name',
        'marque' => 'brand',
        'internal reference' => 'internal_reference',
        'reference interne sfmid' => 'internal_reference',
        'supplier reference' => 'supplier_reference',
        'reference fournisseur' => 'supplier_reference',
        'description' => 'description',
        'unite' => 'unit',
        'purchase price' => 'purchase_price',
        'prix achat' => 'purchase_price',
        'prix d achat' => 'purchase_price',
        'sale price' => 'sale_price',
        'prix vente' => 'sale_price',
        'prix de vente' => 'sale_price',
        'physical stock' => 'physical_stock',
        'stock physique initial' => 'physical_stock',
        'stock physique' => 'physical_stock',
        'tool stock' => 'tool_stock',
        'stock outil' => 'tool_stock',
        'alert threshold' => 'alert_threshold',
        'seuil alerte' => 'alert_threshold',
        'seuil d alerte' => 'alert_threshold',
        'stock kind' => 'stock_kind',
        'type de stock' => 'stock_kind',
        'statut' => 'status',
        'client mine' => 'client_name',
        'client / mine' => 'client_name',
        'client' => 'client_name',
        'mine' => 'client_name',
        'reference client' => 'client_reference',
        'appellation client' => 'client_designation',
        'prix' => 'client_sale_price',
        'prix client' => 'client_sale_price',
        'prix client mine' => 'client_sale_price',
    ];

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

    public function importPage(): View
    {
        Gate::authorize('import', Product::class);

        return view('products.import');
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        Gate::authorize('import', Product::class);

        return Response::streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::CSV_TEMPLATE_HEADERS, ';');
            fputcsv($handle, [
                'PRD-EXEMPLE-001',
                'Casque antibruit',
                '',
                '3M',
                'SFMID-CAS-001',
                'FOUR-CAS-001',
                'Exemple a remplacer ou supprimer avant import',
                'piece',
                '7500',
                '12500',
                '20',
                '0',
                '5',
                'Stock commercial',
                'Actif',
                '',
                '',
                '',
                '',
            ], ';');
            fputcsv($handle, [
                'PRD-EXEMPLE-002',
                'Gants de protection',
                '',
                'Ansell',
                'SFMID-GAN-002',
                'FOUR-GAN-002',
                'Exemple a remplacer ou supprimer avant import',
                'paire',
                '1500',
                '3000',
                '100',
                '0',
                '20',
                'Stock commercial',
                'Actif',
                '',
                '',
                '',
                '',
            ], ';');
            fclose($handle);
        }, 'template-import-produits.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importCsv(Request $request, ActivityLogger $activityLogger, StockSiteInventory $inventory): RedirectResponse
    {
        Gate::authorize('import', Product::class);

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'max:20480', 'mimes:csv,txt'],
        ]);

        $file = new SplFileObject($data['csv_file']->getRealPath());
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $headerRow = $this->readCsvHeader($file);

        if ($headerRow === []) {
            return back()->with('error', 'Le fichier CSV est vide.');
        }

        $delimiter = $this->detectCsvDelimiter($headerRow);
        $file->setCsvControl($delimiter);
        $file->rewind();

        $headers = $this->normalizeCsvHeaders((array) $file->fgetcsv());
        $missingHeaders = array_diff(['code', 'name'], $headers);

        if ($missingHeaders !== []) {
            return back()->with('error', 'Le fichier CSV doit contenir au minimum les colonnes code et name.');
        }

        $headerIndexes = array_flip($headers);
        $categoriesByName = ProductCategory::query()
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => $id])
            ->all();
        $categoryIds = ProductCategory::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowedCategoryIds = array_flip($categoryIds);
        $clientsByNameOrCode = Client::query()
            ->get(['id', 'code', 'name'])
            ->flatMap(fn (Client $client) => [
                mb_strtolower(trim((string) $client->name)) => $client->id,
                mb_strtolower(trim((string) $client->code)) => $client->id,
            ])
            ->filter(fn ($id, $key) => $key !== '')
            ->all();
        $existingProductIdsByCode = Product::query()
            ->pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [mb_strtolower((string) $code) => (int) $id])
            ->all();
        $seenCodes = [];
        $pendingClientReferences = [];
        $affectedCodes = [];
        $now = now();
        $chunk = [];
        $lineNumber = 1;
        $created = 0;
        $updated = 0;
        $clientReferencesUpdated = 0;
        $skipped = 0;
        $errors = [];

        while (! $file->eof()) {
            $row = $file->fgetcsv();
            $lineNumber++;

            if ($this->isEmptyCsvRow($row)) {
                continue;
            }

            $rowData = $this->csvRowToArray((array) $row, $headerIndexes);
            $code = trim((string) ($rowData['code'] ?? ''));
            $normalizedCode = mb_strtolower($code);

            if ($code === '') {
                $skipped++;
                $this->pushImportError($errors, "Ligne {$lineNumber}: code obligatoire.");
                continue;
            }

            if (isset($seenCodes[$normalizedCode])) {
                if ($this->hasClientReferenceImportData($rowData)) {
                    $pendingClientReferences[] = [
                        'line' => $lineNumber,
                        'product_code' => $code,
                        'data' => $rowData,
                    ];

                    continue;
                }

                $skipped++;
                continue;
            }

            $payload = $this->buildProductImportPayload($rowData, $allowedCategoryIds, $categoriesByName, $request->user()->id, $now, $lineNumber, $errors);

            if ($payload === null) {
                $skipped++;
                continue;
            }

            $seenCodes[$normalizedCode] = true;

            if (isset($existingProductIdsByCode[$normalizedCode])) {
                $updatePayload = $payload;
                unset($updatePayload['code'], $updatePayload['created_by'], $updatePayload['created_at']);

                Product::query()
                    ->whereKey($existingProductIdsByCode[$normalizedCode])
                    ->update($updatePayload);

                $affectedCodes[] = $code;
                $updated++;

                if ($this->hasClientReferenceImportData($rowData)) {
                    $pendingClientReferences[] = [
                        'line' => $lineNumber,
                        'product_code' => $code,
                        'data' => $rowData,
                    ];
                }

                continue;
            }

            $chunk[] = $payload;
            $affectedCodes[] = $code;

            if ($this->hasClientReferenceImportData($rowData)) {
                $pendingClientReferences[] = [
                    'line' => $lineNumber,
                    'product_code' => $code,
                    'data' => $rowData,
                ];
            }

            if (count($chunk) >= 500) {
                $created += Product::query()->insertOrIgnore($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $created += Product::query()->insertOrIgnore($chunk);
        }

        Product::query()
            ->whereIn('code', array_values(array_unique($affectedCodes)))
            ->chunkById(250, function ($products) use ($inventory): void {
                foreach ($products as $product) {
                    $this->syncProductDefaultSiteStock($product, $inventory);
                }
            });

        if ($pendingClientReferences !== []) {
            $productIdsByCode = Product::query()
                ->whereIn('code', collect($pendingClientReferences)->pluck('product_code')->unique()->all())
                ->pluck('id', 'code')
                ->mapWithKeys(fn ($id, $code) => [mb_strtolower((string) $code) => (int) $id])
                ->all();

            foreach ($pendingClientReferences as $reference) {
                if ($this->upsertImportedClientReference($reference, $productIdsByCode, $clientsByNameOrCode, $request->user()->id, $errors)) {
                    $clientReferencesUpdated++;
                }
            }
        }

        $activityLogger->log(
            action: 'imported_csv',
            module: 'products',
            description: "Import CSV produits: {$created} cree(s), {$updated} modifie(s), {$clientReferencesUpdated} reference(s) client, {$skipped} ignore(s).",
            newValues: [
                'created' => $created,
                'updated' => $updated,
                'client_references_updated' => $clientReferencesUpdated,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
        );

        return redirect()
            ->route('products.import.create')
            ->with('success', "Import termine: {$created} produit(s) cree(s), {$updated} produit(s) modifie(s), {$clientReferencesUpdated} reference(s) client/mine, {$skipped} ligne(s) ignoree(s).")
            ->with('import_progress', 100)
            ->with('import_errors', $errors);
    }

    public function store(StoreProductRequest $request, ActivityLogger $activityLogger, StockSiteInventory $inventory): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $product = DB::transaction(function () use ($request, $activityLogger, $inventory): Product {
            $data = $request->validated();

            $product = Product::query()->create([
                ...$data,
                'code' => $data['code'] ?: $this->generateProductCode(),
                'reserved_stock' => 0,
                'suspense_stock' => 0,
                'created_by' => $request->user()->id,
            ]);

            $this->syncProductDefaultSiteStock($product, $inventory);

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

    public function searchForDocumentLines(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);

        $search = $request->string('q')->trim()->toString();
        $clientId = $request->integer('client_id') ?: null;
        $productId = $request->integer('product_id') ?: null;

        $products = Product::query()
            ->with([
                'clientPrices' => fn ($query) => $query->when($clientId, fn ($query) => $query->where('client_id', $clientId)),
                'stockSiteStocks',
            ])
            ->active()
            ->commercial()
            ->when($productId, fn ($query) => $query->whereKey($productId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('internal_reference', 'like', "%{$search}%")
                        ->orWhere('supplier_reference', 'like', "%{$search}%")
                        ->orWhereHas('clientPrices', function ($query) use ($search): void {
                            $query->where('client_reference', 'like', "%{$search}%")
                                ->orWhere('client_designation', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json($products->map(function (Product $product) {
            $reference = $product->clientPrices->first();

            return [
                'id' => $product->id,
                'code' => $product->code,
                'internal_reference' => $product->internal_reference,
                'name' => $product->name,
                'unit' => $product->unit,
                'sale_price' => (float) ($reference?->sale_price ?: $product->sale_price),
                'discount_rate' => (float) ($reference?->discount_rate ?: 0),
                'client_reference' => $reference?->client_reference,
                'client_designation' => $reference?->client_designation,
                'site_stocks' => $product->stockSiteStocks->mapWithKeys(fn ($stock) => [
                    $stock->stock_site_id => [
                        'physical_stock' => (float) $stock->physical_stock,
                    ],
                ]),
            ];
        })->values());
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

    public function update(UpdateProductRequest $request, Product $product, ActivityLogger $activityLogger, StockSiteInventory $inventory): RedirectResponse
    {
        Gate::authorize('update', $product);

        DB::transaction(function () use ($request, $product, $activityLogger, $inventory): void {
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
            $oldStockValues = $product->only(['physical_stock', 'reserved_stock', 'suspense_stock', 'tool_stock']);

            $product->update($request->validated());

            if ($this->stockValuesChanged($oldStockValues, $product->fresh())) {
                $this->syncProductDefaultSiteStock($product->refresh(), $inventory);
            }

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

    public function bulkDestroy(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('bulkDelete', Product::class);

        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::query()
            ->whereIn('id', $data['product_ids'])
            ->get();

        $deleted = [];
        $skipped = [];

        DB::transaction(function () use ($products, &$deleted, &$skipped, $activityLogger): void {
            foreach ($products as $product) {
                if ($product->proformaItems()->exists() || $product->deliveryNoteItems()->exists() || $product->invoiceItems()->exists()) {
                    $skipped[] = "{$product->code} - {$product->name}";
                    continue;
                }

                $deleted[] = "{$product->code} - {$product->name}";
                $product->delete();
            }

            if ($deleted !== [] || $skipped !== []) {
                $activityLogger->log(
                    action: 'bulk_deleted',
                    module: 'products',
                    description: count($deleted).' produit(s) supprime(s) en lot, '.count($skipped).' ignore(s).',
                    oldValues: [
                        'deleted' => $deleted,
                        'skipped' => $skipped,
                    ],
                );
            }
        });

        $message = count($deleted).' produit(s) supprime(s).';

        if ($skipped !== []) {
            $message .= ' '.count($skipped).' produit(s) deja utilise(s) ignore(s).';
        }

        return redirect()
            ->route('products.index')
            ->with($deleted !== [] ? 'success' : 'error', $message);
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

    private function readCsvHeader(SplFileObject $file): array
    {
        $file->rewind();
        $row = $file->fgets();

        return $row === false ? [] : [$row];
    }

    private function detectCsvDelimiter(array $headerRow): string
    {
        $line = (string) ($headerRow[0] ?? '');
        $semicolonCount = substr_count($line, ';');
        $commaCount = substr_count($line, ',');

        return $semicolonCount >= $commaCount ? ';' : ',';
    }

    private function normalizeCsvHeaders(array $headers): array
    {
        return array_map(
            function ($header): string {
                $normalized = trim(mb_strtolower((string) $header), " \t\n\r\0\x0B\xEF\xBB\xBF");
                $normalized = str_replace(["'", '’', '-', '_'], [' ', ' ', ' ', ' '], $normalized);
                $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

                return self::CSV_HEADER_ALIASES[$normalized] ?? $normalized;
            },
            $headers
        );
    }

    private function csvRowToArray(array $row, array $headerIndexes): array
    {
        $data = [];

        foreach (self::CSV_HEADERS as $header) {
            $data[$header] = isset($headerIndexes[$header])
                ? trim((string) ($row[$headerIndexes[$header]] ?? ''))
                : '';
        }

        return $data;
    }

    private function isEmptyCsvRow(mixed $row): bool
    {
        if (! is_array($row)) {
            return true;
        }

        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function buildProductImportPayload(
        array $row,
        array $allowedCategoryIds,
        array $categoriesByName,
        int $userId,
        mixed $now,
        int $lineNumber,
        array &$errors
    ): ?array {
        $name = trim((string) ($row['name'] ?? ''));

        if ($name === '') {
            $this->pushImportError($errors, "Ligne {$lineNumber}: nom obligatoire.");
            return null;
        }

        $stockKind = $this->normalizeImportStockKind($row['stock_kind']);
        $status = $this->normalizeImportStatus($row['status']);

        if (! ProductStockKind::tryFrom($stockKind)) {
            $this->pushImportError($errors, "Ligne {$lineNumber}: stock_kind invalide ({$stockKind}).");
            return null;
        }

        if (! ProductStatus::tryFrom($status)) {
            $this->pushImportError($errors, "Ligne {$lineNumber}: status invalide ({$status}).");
            return null;
        }

        $categoryId = $this->resolveImportCategoryId($row, $allowedCategoryIds, $categoriesByName);

        if ($categoryId === false) {
            $this->pushImportError($errors, "Ligne {$lineNumber}: categorie introuvable.");
            return null;
        }

        return [
            'product_category_id' => $categoryId,
            'code' => trim((string) $row['code']),
            'name' => $name,
            'brand' => $row['brand'] ?: null,
            'internal_reference' => $row['internal_reference'] ?: null,
            'supplier_reference' => $row['supplier_reference'] ?: null,
            'description' => $row['description'] ?: null,
            'unit' => $row['unit'] ?: 'piece',
            'purchase_price' => $this->decimalFromCsv($row['purchase_price']),
            'sale_price' => $this->decimalFromCsv($row['sale_price']),
            'physical_stock' => $this->decimalFromCsv($row['physical_stock']),
            'reserved_stock' => 0,
            'suspense_stock' => 0,
            'tool_stock' => $this->decimalFromCsv($row['tool_stock']),
            'alert_threshold' => $this->decimalFromCsv($row['alert_threshold']),
            'stock_kind' => $stockKind,
            'status' => $status,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function resolveImportCategoryId(array $row, array $allowedCategoryIds, array $categoriesByName): int|false|null
    {
        $categoryId = trim((string) ($row['category_id'] ?? ''));

        if ($categoryId !== '') {
            $categoryId = (int) $categoryId;

            return isset($allowedCategoryIds[$categoryId]) ? $categoryId : false;
        }

        $categoryName = mb_strtolower(trim((string) ($row['category_name'] ?? '')));

        if ($categoryName === '') {
            return null;
        }

        return isset($categoriesByName[$categoryName]) ? (int) $categoriesByName[$categoryName] : false;
    }

    private function decimalFromCsv(mixed $value): float
    {
        $normalized = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return $normalized === '' || ! is_numeric($normalized) ? 0 : max(0, (float) $normalized);
    }

    private function hasClientReferenceImportData(array $row): bool
    {
        return trim((string) ($row['client_name'] ?? '')) !== ''
            || trim((string) ($row['client_reference'] ?? '')) !== ''
            || trim((string) ($row['client_designation'] ?? '')) !== ''
            || trim((string) ($row['client_sale_price'] ?? '')) !== '';
    }

    private function upsertImportedClientReference(
        array $reference,
        array $productIdsByCode,
        array $clientsByNameOrCode,
        int $userId,
        array &$errors
    ): bool {
        $row = $reference['data'];
        $lineNumber = $reference['line'];
        $productCode = mb_strtolower((string) $reference['product_code']);
        $clientName = mb_strtolower(trim((string) ($row['client_name'] ?? '')));
        $clientReference = trim((string) ($row['client_reference'] ?? ''));

        if ($clientName === '') {
            $this->pushImportError($errors, "Ligne {$lineNumber}: Client / Mine obligatoire pour la reference client.");
            return false;
        }

        if ($clientReference === '') {
            $this->pushImportError($errors, "Ligne {$lineNumber}: Reference client obligatoire.");
            return false;
        }

        if (! isset($clientsByNameOrCode[$clientName])) {
            $this->pushImportError($errors, "Ligne {$lineNumber}: client/mine introuvable ({$row['client_name']}).");
            return false;
        }

        if (! isset($productIdsByCode[$productCode])) {
            $this->pushImportError($errors, "Ligne {$lineNumber}: produit introuvable pour la reference client.");
            return false;
        }

        ClientProductPrice::query()->updateOrCreate(
            [
                'client_id' => $clientsByNameOrCode[$clientName],
                'product_id' => $productIdsByCode[$productCode],
            ],
            [
                'client_reference' => $clientReference,
                'client_designation' => $row['client_designation'] ?: null,
                'sale_price' => $this->decimalFromCsv($row['client_sale_price']) ?: $this->decimalFromCsv($row['sale_price']),
                'discount_rate' => 0,
                'created_by' => $userId,
            ]
        );

        return true;
    }

    private function normalizeImportStockKind(mixed $value): string
    {
        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'commercial', 'stock commercial' => ProductStockKind::Commercial->value,
            'tool', 'outil', 'stock outil' => ProductStockKind::Tool->value,
            default => trim((string) $value),
        };
    }

    private function normalizeImportStatus(mixed $value): string
    {
        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'active', 'actif' => ProductStatus::Active->value,
            'inactive', 'inactif' => ProductStatus::Inactive->value,
            'obsolete', 'obsolète' => ProductStatus::Obsolete->value,
            default => trim((string) $value),
        };
    }

    private function pushImportError(array &$errors, string $message): void
    {
        if (count($errors) < 30) {
            $errors[] = $message;
        }
    }

    private function syncProductDefaultSiteStock(Product $product, StockSiteInventory $inventory): void
    {
        $row = $inventory->stockRow($product, $inventory->defaultSite());

        $row->forceFill([
            'physical_stock' => (float) $product->physical_stock,
            'reserved_stock' => (float) $product->reserved_stock,
            'suspense_stock' => (float) $product->suspense_stock,
            'tool_stock' => (float) $product->tool_stock,
        ])->save();

        $inventory->syncProductTotals($product);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function stockValuesChanged(array $oldValues, Product $product): bool
    {
        foreach (['physical_stock', 'reserved_stock', 'suspense_stock', 'tool_stock'] as $field) {
            if (round((float) ($oldValues[$field] ?? 0), 3) !== round((float) $product->{$field}, 3)) {
                return true;
            }
        }

        return false;
    }
}
