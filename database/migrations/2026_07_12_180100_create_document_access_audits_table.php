<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attachment_id')->nullable()->constrained('transaction_attachments')->nullOnDelete();
            $table->string('document_version', 50)->nullable();
            $table->string('action_type', 20);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('status', 20)->default('success');
            $table->string('failure_reason')->nullable();
            $table->boolean('watermark_applied')->default(false);
            $table->timestamps();

            $table->index(['attachment_id', 'action_type']);
            $table->index(['user_id', 'created_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_audits');
    }
};
