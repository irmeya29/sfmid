<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('proformas', 'incoterm')) {
            Schema::table('proformas', function (Blueprint $table): void {
                $table->dropColumn('incoterm');
            });
        }

        if (Schema::hasColumn('invoices', 'incoterm')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('incoterm');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('proformas', 'incoterm')) {
            Schema::table('proformas', function (Blueprint $table): void {
                $table->string('incoterm')->nullable()->after('subject');
            });
        }

        if (! Schema::hasColumn('invoices', 'incoterm')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->string('incoterm')->nullable()->after('subject');
            });
        }
    }
};
