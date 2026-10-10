<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hanel_storage_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_shelves')->default(20)->after('access_point');
            $table->unsignedTinyInteger('compartments_per_shelf')->default(8)->after('total_shelves');
        });

        Schema::table('hanel_storage_items', function (Blueprint $table) {
            $table->string('hanel_sync_status', 20)->default('pending')->after('last_job_number');
            $table->text('hanel_sync_message')->nullable()->after('hanel_sync_status');
            $table->timestamp('hanel_synced_at')->nullable()->after('hanel_sync_message');
        });
    }

    public function down(): void
    {
        Schema::table('hanel_storage_settings', function (Blueprint $table) {
            $table->dropColumn(['total_shelves', 'compartments_per_shelf']);
        });

        Schema::table('hanel_storage_items', function (Blueprint $table) {
            $table->dropColumn(['hanel_sync_status', 'hanel_sync_message', 'hanel_synced_at']);
        });
    }
};
