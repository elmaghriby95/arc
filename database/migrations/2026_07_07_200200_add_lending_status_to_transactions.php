<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('lending_status', 32)->default('available')->after('notes');
            $table->foreignId('active_lending_request_id')
                ->nullable()
                ->after('lending_status')
                ->constrained('lending_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_lending_request_id');
            $table->dropColumn('lending_status');
        });
    }
};
