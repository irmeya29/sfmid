<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('tax_regime', 255)->nullable();
            $table->string('legal_form', 255)->nullable();
            $table->string('cnss', 100)->nullable();
            $table->string('share_capital', 100)->nullable();
            $table->string('postal_address', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn(['tax_regime', 'legal_form', 'cnss', 'share_capital', 'postal_address']);
        });
    }
};
