<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'folder_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('folder_id')
                ->references('id')
                ->on('folders')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'folder_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('folder_id')
                ->references('id')
                ->on('folders')
                ->nullOnDelete();
        });
    }
};
