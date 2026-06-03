<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proformas', function (Blueprint $table): void {
            $table->string('incoterm')->nullable()->after('valid_until');
            $table->string('currency')->default('FCFA')->after('incoterm');
            $table->string('payment_terms')->nullable()->after('currency');
            $table->string('delivery_delay')->nullable()->after('payment_terms');
        });

        Schema::table('proforma_items', function (Blueprint $table): void {
            $table->decimal('line_subtotal', 15, 2)->default(0)->after('unit_price');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            $table->decimal('line_total_ht', 15, 2)->default(0)->after('tax_amount');
            $table->decimal('line_total_ttc', 15, 2)->default(0)->after('line_total_ht');
        });
    }

    public function down(): void
    {
        Schema::table('proforma_items', function (Blueprint $table): void {
            $table->dropColumn(['line_subtotal', 'tax_rate', 'tax_amount', 'line_total_ht', 'line_total_ttc']);
        });

        Schema::table('proformas', function (Blueprint $table): void {
            $table->dropColumn(['incoterm', 'currency', 'payment_terms', 'delivery_delay']);
        });
    }
};
