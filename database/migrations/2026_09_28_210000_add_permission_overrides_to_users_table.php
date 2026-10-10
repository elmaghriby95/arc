<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('granted_permissions')->nullable()->after('view_descendant_units');
            $table->json('revoked_permissions')->nullable()->after('granted_permissions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['granted_permissions', 'revoked_permissions']);
        });
    }
};
