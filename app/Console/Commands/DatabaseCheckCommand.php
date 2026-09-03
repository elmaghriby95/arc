<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseCheckCommand extends Command
{
    protected $signature = 'arc:db-check';

    protected $description = 'Verify critical ARC database tables exist for demo/production';

    /** @var list<string> */
    private const CRITICAL_TABLES = [
        'users',
        'roles',
        'departments',
        'folders',
        'languages',
        'transactions',
        'transaction_statuses',
        'transaction_attachments',
        'documents',
        'system_settings',
        'reference_number_settings',
        'translation_keys',
        'translations',
        'sessions',
        'migrations',
    ];

    public function handle(): int
    {
        $this->info('ARC database table check');
        $this->newLine();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            $this->error('Database connection failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $missing = [];
        $present = [];

        foreach (self::CRITICAL_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $present[] = $table;
                $this->line("<info>OK</info>  {$table}");
            } else {
                $missing[] = $table;
                $this->line("<error>MISSING</error>  {$table}");
            }
        }

        $this->newLine();
        $this->line('Present: '.count($present).' / '.count(self::CRITICAL_TABLES));

        if ($missing !== []) {
            $this->newLine();
            $this->error('Missing tables: '.implode(', ', $missing));
            $this->warn('Run: php artisan migrate --force');

            return self::FAILURE;
        }

        $pending = DB::table('migrations')
            ->count();

        $this->info("All critical tables exist. Recorded migrations: {$pending}");

        return self::SUCCESS;
    }
}
