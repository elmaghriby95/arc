<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hanel_storage_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('reference_number', 64)->nullable();
            $table->string('article_number', 64)->nullable();
            $table->unsignedSmallInteger('shelf_number');
            $table->unsignedTinyInteger('compartment_number')->nullable();
            $table->unsignedTinyInteger('compartment_depth')->nullable();
            $table->string('file_name')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type', 127)->nullable();
            $table->string('last_job_number', 10)->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('reference_number');
            $table->index('article_number');
            $table->index('shelf_number');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hanel_storage_items');
    }
};
