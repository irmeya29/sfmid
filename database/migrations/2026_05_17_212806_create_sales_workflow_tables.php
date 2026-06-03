<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proformas', function (Blueprint $table): void {
            $table->id();

            $table->string('number')->unique();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('client_delivery_site_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('status')->default('draft')->index();

            $table->date('issue_date');
            $table->date('valid_until')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->text('terms')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamp('converted_to_delivery_note_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['status', 'issue_date']);
        });

        Schema::create('proforma_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('proforma_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('product_code');
            $table->string('product_name');
            $table->string('unit');

            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);

            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('line_total', 15, 2);

            $table->timestamps();

            $table->index(['proforma_id', 'product_id']);
        });

        Schema::create('delivery_notes', function (Blueprint $table): void {
            $table->id();

            $table->string('number')->unique();

            $table->foreignId('proforma_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('client_delivery_site_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('status')->default('draft')->index();

            $table->date('planned_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('delivery_address')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->foreignId('stock_moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('stock_moved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['status', 'delivered_at']);
            $table->index(['stock_moved_at']);
        });

        Schema::create('delivery_note_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('delivery_note_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('proforma_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('product_code');
            $table->string('product_name');
            $table->string('unit');

            $table->decimal('quantity', 15, 3);
            $table->decimal('delivered_quantity', 15, 3)->default(0);

            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);

            $table->timestamps();

            $table->index(['delivery_note_id', 'product_id']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();

            $table->string('number')->unique();

            $table->foreignId('delivery_note_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status')->default('draft')->index();

            $table->date('issue_date');
            $table->date('due_date')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);

            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['status', 'issue_date']);
            $table->index(['due_date', 'balance_due']);
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('delivery_note_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('product_code');
            $table->string('product_name');
            $table->string('unit');

            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);

            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);

            $table->timestamps();

            $table->index(['invoice_id', 'product_id']);
        });

        Schema::create('stock_suspenses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('delivery_note_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('delivery_note_item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('quantity', 15, 3);
            $table->decimal('closed_quantity', 15, 3)->default(0);

            $table->string('status')->default('open')->index();

            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->text('closing_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique('delivery_note_item_id');
            $table->index(['client_id', 'status']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            $table->string('number')->unique();

            $table->foreignId('invoice_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status')->default('draft')->index();

            $table->decimal('amount', 15, 2);
            $table->date('payment_date');

            $table->string('method')->index();
            $table->string('reference')->nullable()->index();
            $table->string('attachment_path')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'status']);
            $table->index(['payment_date', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('stock_suspenses');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('proforma_items');
        Schema::dropIfExists('proformas');
    }
};
