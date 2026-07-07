<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_qr_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('display_size')->default(160);
            $table->unsignedSmallInteger('print_size')->default(280);
            $table->timestamps();
        });

        DB::table('transaction_qr_settings')->insert([
            'display_size' => 160,
            'print_size' => 280,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_qr_settings');
    }
};
