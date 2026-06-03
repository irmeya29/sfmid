<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('ifu')->nullable();
            $table->string('rccm')->nullable();
            $table->string('contact_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_reference')->nullable();
            $table->decimal('last_purchase_price', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['supplier_id', 'product_id']);
        });

        Schema::create('purchase_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->date('request_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft')->index();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_code');
            $table->string('product_name');
            $table->string('unit')->default('piece');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('supplier_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->string('supplier_invoice_number')->nullable()->index();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('unpaid')->index();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('total', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('method')->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('supplier_purchase_order_items');
        Schema::dropIfExists('supplier_purchase_orders');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('supplier_product');
        Schema::dropIfExists('suppliers');
    }
};
