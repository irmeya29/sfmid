<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();

            $table->string('key')->unique();
            $table->text('value')->nullable();

            $table->string('type')->default('string');
            $table->string('group')->default('general')->index();

            $table->boolean('is_public')->default(false);

            $table->timestamps();
        });

        Schema::create('document_number_sequences', function (Blueprint $table): void {
            $table->id();

            $table->string('document_type')->unique();
            $table->string('prefix', 30);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);

            $table->string('reset_period')->nullable();
            $table->timestamp('last_generated_at')->nullable();

            $table->timestamps();
        });

        Schema::create('payment_modes', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });

        Schema::create('measurement_units', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_units');
        Schema::dropIfExists('payment_modes');
        Schema::dropIfExists('document_number_sequences');
        Schema::dropIfExists('company_settings');
    }
};
