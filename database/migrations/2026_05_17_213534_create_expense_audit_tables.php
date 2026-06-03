<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->boolean('is_sensitive')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();

            $table->string('number')->unique();

            $table->foreignId('expense_category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status')->default('draft')->index();

            $table->decimal('amount', 15, 2);
            $table->date('expense_date');

            $table->string('beneficiary')->nullable();
            $table->string('payment_method')->nullable()->index();
            $table->string('payment_reference')->nullable()->index();

            $table->string('attachment_path')->nullable();

            $table->text('description');

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

            $table->index(['expense_category_id', 'status']);
            $table->index(['expense_date', 'status']);
        });

        Schema::create('validation_histories', function (Blueprint $table): void {
            $table->id();

            $table->nullableMorphs('document');

            $table->string('action')->index();

            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->text('comment')->nullable();
            $table->text('reason')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['action', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->nullableMorphs('subject');

            $table->string('action')->index();
            $table->string('module')->nullable()->index();

            $table->text('description')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('validation_histories');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
