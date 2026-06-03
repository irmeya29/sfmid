<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'slug']);
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('module')->index();
            $table->string('action')->index();

            $table->boolean('is_sensitive')->default(false)->index();
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['module', 'action']);
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('role_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('role_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
        });

        Schema::create('user_permission_overrides', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_allowed')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reason')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
            $table->index(['user_id', 'is_allowed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission_overrides');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
