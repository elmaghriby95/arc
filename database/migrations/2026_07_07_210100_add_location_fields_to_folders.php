<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->string('cabinet_number', 50)->nullable()->after('name');
            $table->string('row_number', 50)->nullable()->after('cabinet_number');
            $table->string('box_number', 50)->nullable()->after('row_number');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropColumn(['cabinet_number', 'row_number', 'box_number']);
        });
    }
};
