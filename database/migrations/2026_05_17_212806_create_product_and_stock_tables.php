<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'is_active']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('code')->unique();
            $table->string('name');

            $table->string('brand')->nullable();
            $table->string('supplier_reference')->nullable()->index();

            $table->string('unit')->default('piece');

            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);

            $table->decimal('physical_stock', 15, 3)->default(0);
            $table->decimal('reserved_stock', 15, 3)->default(0);
            $table->decimal('suspense_stock', 15, 3)->default(0);
            $table->decimal('tool_stock', 15, 3)->default(0);

            $table->decimal('alert_threshold', 15, 3)->default(0);

            $table->string('stock_kind')->default('commercial')->index();
            $table->string('status')->default('active')->index();

            $table->string('photo_path')->nullable();
            $table->string('technical_sheet_path')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'status']);
            $table->index(['product_category_id', 'status']);
            $table->index(['physical_stock', 'reserved_stock', 'suspense_stock']);
        });

        Schema::create('client_product_prices', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('sale_price', 15, 2);
            $table->decimal('discount_rate', 5, 2)->default(0);

            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['client_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('type')->index();
            $table->string('direction')->index();
            $table->string('status')->default('validated')->index();

            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->nullable();

            $table->decimal('physical_before', 15, 3)->default(0);
            $table->decimal('physical_after', 15, 3)->default(0);

            $table->decimal('reserved_before', 15, 3)->default(0);
            $table->decimal('reserved_after', 15, 3)->default(0);

            $table->decimal('suspense_before', 15, 3)->default(0);
            $table->decimal('suspense_after', 15, 3)->default(0);

            $table->decimal('tool_before', 15, 3)->default(0);
            $table->decimal('tool_after', 15, 3)->default(0);

            $table->nullableMorphs('source');

            $table->text('reason')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('client_product_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
