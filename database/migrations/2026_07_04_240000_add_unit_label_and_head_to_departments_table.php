<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('unit_label')->nullable()->after('name');
            $table->foreignId('head_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_id');
            $table->dropColumn('unit_label');
        });
    }
};
