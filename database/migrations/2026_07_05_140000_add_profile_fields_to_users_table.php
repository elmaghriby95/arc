<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'avatar_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar_path')->nullable()->after('email');
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            });
        }

        if (Schema::hasTable('audit_logs') && DB::getDriverName() !== 'sqlite') {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex(['auditable_type', 'auditable_id']);
            });

            DB::statement('ALTER TABLE audit_logs MODIFY auditable_type VARCHAR(191) NULL');
            DB::statement('ALTER TABLE audit_logs MODIFY auditable_id BIGINT UNSIGNED NULL');

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['auditable_type', 'auditable_id']);
            });
        }

        if (Schema::hasTable('audit_logs')) {
            $indexes = collect(DB::select('SHOW INDEX FROM audit_logs'))
                ->pluck('Key_name')
                ->unique();

            if (! $indexes->contains('audit_logs_user_id_index')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index('user_id');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
            });

            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE audit_logs MODIFY auditable_type VARCHAR(255) NOT NULL');
                DB::statement('ALTER TABLE audit_logs MODIFY auditable_id BIGINT UNSIGNED NOT NULL');
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'last_login_at', 'last_login_ip']);
        });
    }
};
