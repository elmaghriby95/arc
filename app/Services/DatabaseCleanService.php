<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DatabaseCleanService
{
    public const CONFIRMATION_PHRASE = 'تنظيف';

    /**
     * الجداول التي تُفرَّغ عند التنظيف (ترتيب غير مهم مع تعطيل قيود المفاتيح الأجنبية).
     *
     * @var list<string>
     */
    private const CLEANABLE_TABLES = [
        'lending_request_histories',
        'lending_requests',
        'transaction_attachments',
        'transaction_status_histories',
        'transactions',
        'document_access_audits',
        'document_versions',
        'document_tag',
        'documents',
        'tags',
        'folders',
        'departments',
        'document_types',
        'transaction_types',
        'audit_logs',
        'notifications',
    ];

    /** @return array<string, int> */
    public function previewCounts(): array
    {
        $counts = [];

        foreach (self::CLEANABLE_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $counts[$table] = (int) DB::table($table)->count();
        }

        $counts['non_admin_users_department_links'] = User::query()
            ->whereNotNull('department_id')
            ->whereDoesntHave('role', fn ($q) => $q->where('slug', Role::SUPER_ADMIN_SLUG))
            ->count();

        return $counts;
    }

    /** @return array{tables: array<string, int>, files_removed: bool} */
    public function clean(): array
    {
        $before = $this->previewCounts();

        DB::transaction(function (): void {
            // فصل المستخدمين عن الوحدات قبل حذف الهيكل التنظيمي
            User::query()->update(['department_id' => null]);

            if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'active_lending_request_id')) {
                DB::table('transactions')->update(['active_lending_request_id' => null]);
            }

            if (Schema::hasTable('departments')) {
                if (Schema::hasColumn('departments', 'head_id')) {
                    DB::table('departments')->update(['head_id' => null]);
                }
                if (Schema::hasColumn('departments', 'parent_id')) {
                    DB::table('departments')->update(['parent_id' => null]);
                }
            }

            if (Schema::hasTable('folders') && Schema::hasColumn('folders', 'parent_id')) {
                DB::table('folders')->update(['parent_id' => null]);
            }

            Schema::disableForeignKeyConstraints();

            try {
                foreach (self::CLEANABLE_TABLES as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    DB::table($table)->delete();
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });

        $this->purgeStoredFiles();

        return [
            'tables' => $before,
            'files_removed' => true,
        ];
    }

    private function purgeStoredFiles(): void
    {
        $local = Storage::disk('local');

        foreach (['transaction-attachments', 'documents', 'document-versions'] as $directory) {
            if ($local->exists($directory)) {
                $local->deleteDirectory($directory);
            }
        }
    }
}
