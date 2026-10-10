<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lending_requests', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('requested_by')
                ->constrained()
                ->nullOnDelete();
        });

        $rows = DB::table('lending_requests')
            ->join('transactions', 'transactions.id', '=', 'lending_requests.transaction_id')
            ->whereNull('lending_requests.department_id')
            ->get([
                'lending_requests.id as id',
                'transactions.department_id as department_id',
            ]);

        foreach ($rows as $row) {
            DB::table('lending_requests')
                ->where('id', $row->id)
                ->update(['department_id' => $row->department_id]);
        }
    }

    public function down(): void
    {
        Schema::table('lending_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
