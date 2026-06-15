<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->boolean('direct_stock_enabled')->default(false)->after('client_id');
            $table->foreignId('stock_site_id')
                ->nullable()
                ->after('direct_stock_enabled')
                ->constrained('stock_sites')
                ->nullOnDelete();
            $table->foreignId('stock_moved_by')
                ->nullable()
                ->after('stock_site_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('stock_moved_at')->nullable()->after('stock_moved_by');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stock_moved_by');
            $table->dropConstrainedForeignId('stock_site_id');
            $table->dropColumn(['direct_stock_enabled', 'stock_moved_at']);
        });
    }
};
