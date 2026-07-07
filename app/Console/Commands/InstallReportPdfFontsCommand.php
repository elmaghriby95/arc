<?php

namespace App\Console\Commands;

use App\Support\Reports\PdfFontRegistry;
use Illuminate\Console\Command;

class InstallReportPdfFontsCommand extends Command
{
    protected $signature = 'reports:install-pdf-fonts';

    protected $description = 'Download and register Cairo fonts for PDF report exports';

    public function handle(PdfFontRegistry $registry): int
    {
        $this->info('Installing Cairo fonts for PDF reports...');

        if ($registry->cairoFontsInstalled() && $registry->registerInstalledFonts()) {
            $this->info('Cairo fonts registered successfully.');

            return self::SUCCESS;
        }

        if (! $registry->installFonts()) {
            $this->error('Failed to install Cairo fonts.');
            $this->line('Place Cairo-Regular.ttf and Cairo-Bold.ttf in resources/fonts/cairo/ then run this command again.');
            $this->line('Or ensure the server can download from GitHub and storage/fonts is writable.');

            return self::FAILURE;
        }

        $this->info('Cairo fonts installed and registered successfully.');

        return self::SUCCESS;
    }
}
