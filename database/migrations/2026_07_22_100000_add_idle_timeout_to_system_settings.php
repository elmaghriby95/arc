<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('idle_timeout_minutes')->default(1)->after('support_phone');
        });

        DB::table('system_settings')->update([
            'idle_timeout_minutes' => 1,
        ]);
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('idle_timeout_minutes');
        });
    }
};
