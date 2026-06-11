<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proformas', function (Blueprint $table): void {
            $table->string('subject')->nullable()->after('valid_until');
        });

        Schema::table('delivery_notes', function (Blueprint $table): void {
            $table->string('subject')->nullable()->after('planned_delivery_date');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('subject')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('subject');
        });

        Schema::table('delivery_notes', function (Blueprint $table): void {
            $table->dropColumn('subject');
        });

        Schema::table('proformas', function (Blueprint $table): void {
            $table->dropColumn('subject');
        });
    }
};
