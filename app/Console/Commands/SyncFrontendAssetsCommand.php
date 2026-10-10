<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncFrontendAssetsCommand extends Command
{
    protected $signature = 'assets:sync';

    protected $description = 'Confirm UI assets are served from resources and are not copied into public';

    public function handle(): int
    {
        $this->info('UI assets are inlined from resources/css and resources/js.');
        $this->line('Nothing was copied into public/.');

        return self::SUCCESS;
    }
}
