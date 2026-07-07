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
            $table->unsignedSmallInteger('logo_navbar_height')->default(28)->after('logo_path');
            $table->unsignedSmallInteger('logo_navbar_max_width')->default(100)->after('logo_navbar_height');
            $table->unsignedSmallInteger('logo_login_height')->default(40)->after('logo_navbar_max_width');
            $table->unsignedSmallInteger('logo_login_max_width')->default(120)->after('logo_login_height');
        });

        DB::table('system_settings')->update([
            'logo_navbar_height' => 28,
            'logo_navbar_max_width' => 100,
            'logo_login_height' => 40,
            'logo_login_max_width' => 120,
        ]);
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'logo_navbar_height',
                'logo_navbar_max_width',
                'logo_login_height',
                'logo_login_max_width',
            ]);
        });
    }
};
