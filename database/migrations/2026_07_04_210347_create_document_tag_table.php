<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_tag', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['document_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_tag');
    }
};
