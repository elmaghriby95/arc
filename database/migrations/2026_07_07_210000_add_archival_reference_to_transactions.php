<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'archival_reference')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('archival_reference', 191)->nullable()->after('reference_number');
            });
        }

        DB::table('transactions')
            ->whereNull('archival_reference')
            ->update(['archival_reference' => DB::raw('reference_number')]);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transactions MODIFY archival_reference VARCHAR(191) NOT NULL');
        }

        $indexes = collect(DB::select('SHOW INDEX FROM transactions'))
            ->pluck('Key_name')
            ->unique();

        if (! $indexes->contains('transactions_archival_reference_unique')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unique('archival_reference');
            });
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['archival_reference']);
            $table->dropColumn('archival_reference');
        });
    }
};
