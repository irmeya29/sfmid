<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sites', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('can_store')->default(true)->index();
            $table->boolean('can_sell')->default(false)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_stock_site', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_site_id')->constrained('stock_sites')->cascadeOnDelete();
            $table->decimal('physical_stock', 15, 3)->default(0);
            $table->decimal('reserved_stock', 15, 3)->default(0);
            $table->decimal('suspense_stock', 15, 3)->default(0);
            $table->decimal('tool_stock', 15, 3)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'stock_site_id']);
            $table->index(['stock_site_id', 'physical_stock']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('stock_site_id')
                ->nullable()
                ->after('product_id')
                ->constrained('stock_sites')
                ->nullOnDelete();

            $table->foreignId('destination_stock_site_id')
                ->nullable()
                ->after('stock_site_id')
                ->constrained('stock_sites')
                ->nullOnDelete();
        });

        Schema::table('delivery_notes', function (Blueprint $table): void {
            $table->foreignId('stock_site_id')
                ->nullable()
                ->after('client_delivery_site_id')
                ->constrained('stock_sites')
                ->nullOnDelete();
        });

        Schema::table('stock_suspenses', function (Blueprint $table): void {
            $table->foreignId('stock_site_id')
                ->nullable()
                ->after('product_id')
                ->constrained('stock_sites')
                ->nullOnDelete();
        });

        $defaultSiteId = DB::table('stock_sites')->insertGetId([
            'code' => 'SITE-PRINCIPAL',
            'name' => 'Site principal',
            'description' => 'Site de stock par defaut cree automatiquement.',
            'can_store' => true,
            'can_sell' => true,
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Product::query()
            ->select(['id', 'physical_stock', 'reserved_stock', 'suspense_stock', 'tool_stock'])
            ->chunkById(500, function ($products) use ($defaultSiteId): void {
                $now = now();
                $rows = $products->map(fn (Product $product): array => [
                    'product_id' => $product->id,
                    'stock_site_id' => $defaultSiteId,
                    'physical_stock' => $product->physical_stock,
                    'reserved_stock' => $product->reserved_stock,
                    'suspense_stock' => $product->suspense_stock,
                    'tool_stock' => $product->tool_stock,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('product_stock_site')->insert($rows);
            });

        DB::table('stock_movements')->update(['stock_site_id' => $defaultSiteId]);
        DB::table('delivery_notes')->update(['stock_site_id' => $defaultSiteId]);
        DB::table('stock_suspenses')->update(['stock_site_id' => $defaultSiteId]);
    }

    public function down(): void
    {
        Schema::table('stock_suspenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stock_site_id');
        });

        Schema::table('delivery_notes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stock_site_id');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('destination_stock_site_id');
            $table->dropConstrainedForeignId('stock_site_id');
        });

        Schema::dropIfExists('product_stock_site');
        Schema::dropIfExists('stock_sites');
    }
};
