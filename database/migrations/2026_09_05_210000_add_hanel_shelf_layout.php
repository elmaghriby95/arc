<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hanel_storage_settings', function (Blueprint $table) {
            $table->json('shelf_layout')->nullable()->after('compartments_per_shelf');
            $table->timestamp('layout_synced_at')->nullable()->after('shelf_layout');
        });

        DB::table('hanel_storage_settings')
            ->whereIn('total_shelves', [19, 20])
            ->update(['total_shelves' => 8]);
    }

    public function down(): void
    {
        Schema::table('hanel_storage_settings', function (Blueprint $table) {
            $table->dropColumn(['shelf_layout', 'layout_synced_at']);
        });
    }
};
