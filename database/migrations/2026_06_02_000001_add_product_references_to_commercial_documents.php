<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('internal_reference')->nullable()->after('brand')->index();
            $table->text('description')->nullable()->after('supplier_reference');
        });

        Schema::table('client_product_prices', function (Blueprint $table): void {
            $table->string('client_reference')->nullable()->after('product_id')->index();
            $table->string('client_designation')->nullable()->after('client_reference');
        });

        foreach (['proforma_items', 'delivery_note_items', 'invoice_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('product_internal_reference')->nullable()->after('product_code');
                $table->string('client_product_reference')->nullable()->after('product_internal_reference');
            });
        }
    }

    public function down(): void
    {
        foreach (['invoice_items', 'delivery_note_items', 'proforma_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['product_internal_reference', 'client_product_reference']);
            });
        }

        Schema::table('client_product_prices', function (Blueprint $table): void {
            $table->dropColumn(['client_reference', 'client_designation']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['internal_reference', 'description']);
        });
    }
};
