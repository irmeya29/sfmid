<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('proforma_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_delivery_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_reference')->nullable()->index();
            $table->date('order_date');
            $table->string('status')->default('validated')->index();
            $table->text('confirmed_terms')->nullable();
            $table->string('attachment_path')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['proforma_id']);
        });

        Schema::create('customer_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proforma_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_code');
            $table->string('product_internal_reference')->nullable();
            $table->string('client_product_reference')->nullable();
            $table->string('product_name');
            $table->string('unit');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_subtotal', 15, 2)->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total_ht', 15, 2)->default(0);
            $table->decimal('line_total_ttc', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();

            $table->index(['customer_order_id', 'product_id']);
        });

        Schema::table('delivery_notes', function (Blueprint $table): void {
            $table->foreignId('customer_order_id')
                ->nullable()
                ->after('proforma_id')
                ->constrained('customer_orders')
                ->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('proforma_id')
                ->nullable()
                ->after('delivery_note_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('customer_order_id')
                ->nullable()
                ->after('proforma_id')
                ->constrained('customer_orders')
                ->nullOnDelete();
        });

        DB::table('document_number_sequences')->updateOrInsert(
            ['document_type' => 'customer_order'],
            [
                'prefix' => 'BCC',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
                'last_generated_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_order_id');
            $table->dropConstrainedForeignId('proforma_id');
        });

        Schema::table('delivery_notes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_order_id');
        });

        Schema::dropIfExists('customer_order_items');
        Schema::dropIfExists('customer_orders');
    }
};
