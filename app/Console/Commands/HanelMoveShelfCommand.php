<?php

namespace App\Console\Commands;

use App\Models\HanelStorageSetting;
use App\Services\HanelStorageClient;
use Illuminate\Console\Command;

class HanelMoveShelfCommand extends Command
{
    protected $signature = 'hanel:move-shelf {shelf : Shelf number (1-999)} {--compartment=1} {--depth=1}';

    protected $description = 'Send get_shelf to the Hänel unit via HostCom TCP (port 2200)';

    public function handle(): int
    {
        $settings = HanelStorageSetting::instance();

        if (! $settings->is_enabled) {
            $this->error('Hänel integration is disabled. Enable it in settings first.');

            return self::FAILURE;
        }

        $shelf = (int) $this->argument('shelf');
        $compartment = (int) $this->option('compartment');
        $depth = (int) $this->option('depth');

        $this->info("Host: {$settings->host}, TCP ports: 2200 then 3500, shelf={$shelf}");

        $result = HanelStorageClient::fromSettings($settings)->moveShelf($shelf, $compartment, $depth);

        $this->line($result['message']);

        if ($result['details'] !== []) {
            $this->newLine();
            $this->line(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
