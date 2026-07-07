<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lending_request_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lending_request_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['lending_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lending_request_histories');
    }
};
