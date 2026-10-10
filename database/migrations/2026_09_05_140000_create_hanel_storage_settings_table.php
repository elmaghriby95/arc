<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hanel_storage_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('protocol', 20)->default('host_com');
            $table->string('host', 191)->default('172.16.1.1');
            $table->unsignedSmallInteger('tcp_port')->default(2200);
            $table->unsignedSmallInteger('http_port')->default(80);
            $table->boolean('use_https')->default(false);
            $table->unsignedTinyInteger('lift_number')->default(1);
            $table->unsignedTinyInteger('access_point')->default(1);
            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->string('last_test_status', 20)->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hanel_storage_settings');
    }
};
