<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_number_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_assign_department')->default(true);
            $table->boolean('allow_previous_years')->default(true);
            $table->boolean('month_optional')->default(true);
            $table->boolean('original_document_number_optional')->default(true);
            $table->boolean('operational_number_enabled')->default(true);
            $table->boolean('prevent_duplicate_numbers')->default(true);
            $table->boolean('audit_number_changes')->default(true);
            $table->boolean('allow_free_format_reference')->default(true);
            $table->boolean('support_multilingual_characters')->default(true);
            $table->string('operational_number_separator', 5)->default('/');
            $table->string('operational_number_format')->default('{department_code}{separator}{year}{separator}{document_type}{separator}{sequence}');
            $table->text('operational_number_disclaimer')->nullable();
            $table->timestamps();
        });

        DB::table('reference_number_settings')->insert([
            'auto_assign_department' => true,
            'allow_previous_years' => true,
            'month_optional' => true,
            'original_document_number_optional' => true,
            'operational_number_enabled' => true,
            'prevent_duplicate_numbers' => true,
            'audit_number_changes' => true,
            'allow_free_format_reference' => true,
            'support_multilingual_characters' => true,
            'operational_number_separator' => '/',
            'operational_number_format' => '{department_code}{separator}{year}{separator}{document_type}{separator}{sequence}',
            'operational_number_disclaimer' => 'الرقم التشغيلي ليس رقماً إشارياً رسمياً — يُستخدم فقط للمستندات التي لا تحتوي على رقم إشاري.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_number_settings');
    }
};
