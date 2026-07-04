<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('required_permission')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('transaction_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_status_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('transaction_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'transaction_status_id']);
        });

        Schema::create('transaction_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('transaction_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->constrained('transaction_statuses')->restrictOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_status_histories');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('transaction_statuses');
    }
};
