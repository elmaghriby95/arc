<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_attachments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('title');
            $table->unsignedSmallInteger('reference_year')->nullable()->after('reference_number');
            $table->unsignedTinyInteger('reference_month')->nullable()->after('reference_year');
            $table->string('original_document_number')->nullable()->after('reference_month');
            $table->boolean('is_operational_number')->default(false)->after('original_document_number');

            $table->index(['reference_number']);
        });
    }

    public function down(): void
    {
        Schema::table('transaction_attachments', function (Blueprint $table) {
            $table->dropIndex(['reference_number']);
            $table->dropColumn([
                'reference_number',
                'reference_year',
                'reference_month',
                'original_document_number',
                'is_operational_number',
            ]);
        });
    }
};
