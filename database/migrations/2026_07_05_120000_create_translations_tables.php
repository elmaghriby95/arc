<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_keys', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100);
            $table->string('key', 191);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->timestamps();

            $table->unique(['translation_key_id', 'language_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('language_id')
                ->nullable()
                ->after('department_id')
                ->constrained('languages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('language_id');
        });

        Schema::dropIfExists('translations');
        Schema::dropIfExists('translation_keys');
    }
};
