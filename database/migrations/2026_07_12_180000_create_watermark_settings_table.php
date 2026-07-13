<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watermark_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedTinyInteger('opacity')->default(18);
            $table->unsignedTinyInteger('font_size')->default(28);
            $table->smallInteger('angle')->default(-45);
            $table->boolean('show_center_text')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->boolean('show_qr_code')->default(true);
            $table->boolean('show_user_name')->default(true);
            $table->boolean('show_user_id')->default(true);
            $table->boolean('show_department')->default(true);
            $table->boolean('show_datetime')->default(true);
            $table->boolean('show_action_type')->default(true);
            $table->boolean('show_transaction_id')->default(true);
            $table->boolean('apply_on_view')->default(true);
            $table->boolean('apply_on_download')->default(true);
            $table->boolean('apply_on_print')->default(true);
            $table->timestamps();
        });

        DB::table('watermark_settings')->insert([
            'is_enabled' => true,
            'opacity' => 18,
            'font_size' => 28,
            'angle' => -45,
            'show_center_text' => true,
            'show_footer' => true,
            'show_qr_code' => true,
            'show_user_name' => true,
            'show_user_id' => true,
            'show_department' => true,
            'show_datetime' => true,
            'show_action_type' => true,
            'show_transaction_id' => true,
            'apply_on_view' => true,
            'apply_on_download' => true,
            'apply_on_print' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('watermark_settings');
    }
};
