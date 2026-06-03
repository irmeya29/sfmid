<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->string('type')->default('other')->index();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            $table->string('ifu')->nullable()->index();
            $table->string('rccm')->nullable()->index();

            $table->unsignedInteger('payment_delay_days')->default(0);
            $table->text('commercial_terms')->nullable();

            $table->string('status')->default('active')->index();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'status']);
        });

        Schema::create('client_contacts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index(['client_id', 'is_primary']);
        });

        Schema::create('client_delivery_sites', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['client_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_delivery_sites');
        Schema::dropIfExists('client_contacts');
        Schema::dropIfExists('clients');
    }
};
