<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_statuses', function (Blueprint $table) {
            $table->string('visibility_scope', 10)->default('unit')->after('required_permission');
        });

        $globalCodes = [
            'REVIEW',
            'APPROVED',
            'INITIAL_ARCHIVE',
            'ARCHIVED',
        ];

        foreach ($globalCodes as $code) {
            DB::table('transaction_statuses')
                ->where('code', $code)
                ->update(['visibility_scope' => 'global']);
        }
    }

    public function down(): void
    {
        Schema::table('transaction_statuses', function (Blueprint $table) {
            $table->dropColumn('visibility_scope');
        });
    }
};
