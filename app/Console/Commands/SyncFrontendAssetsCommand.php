<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncFrontendAssetsCommand extends Command
{
    protected $signature = 'assets:sync';

    protected $description = 'Copy frontend JS/CSS from resources into public so deploys that rely on public/ stay in sync';

    public function handle(): int
    {
        $copied = 0;

        $jsMap = [
            'arc-scan.js' => 'arc-scan.js',
            'transaction-attachments.js' => 'transaction-attachments.js',
            'transaction-create.js' => 'transaction-create.js',
            'navbar.js' => 'app.js',
            'login.js' => 'login.js',
        ];

        foreach ($jsMap as $source => $target) {
            $from = resource_path('js/'.$source);
            $to = public_path('js/'.$target);

            if (! is_readable($from)) {
                $this->warn("Skip missing JS: {$source}");
                continue;
            }

            File::ensureDirectoryExists(dirname($to));
            File::copy($from, $to);
            $this->line("JS  {$source} → public/js/{$target}");
            $copied++;
        }

        $cssFiles = [
            'app.css',
            'transaction-create.css',
        ];

        foreach ($cssFiles as $file) {
            $from = resource_path('css/'.$file);
            $to = public_path('css/'.$file);

            if (! is_readable($from)) {
                $this->warn("Skip missing CSS: {$file}");
                continue;
            }

            File::ensureDirectoryExists(dirname($to));
            File::copy($from, $to);
            $this->line("CSS {$file} → public/css/{$file}");
            $copied++;
        }

        // Keep pdf.js mirrors in public for direct asset() compatibility on older deploys.
        $pdfJsFiles = ['pdf.min.js', 'pdf.worker.min.js'];
        foreach ($pdfJsFiles as $file) {
            $from = resource_path('js/vendor/pdfjs/'.$file);
            $to = public_path('vendor/pdfjs/'.$file);

            if (! is_readable($from)) {
                $this->warn("Skip missing PDF.js: {$file}");
                continue;
            }

            File::ensureDirectoryExists(dirname($to));
            File::copy($from, $to);
            $this->line("PDF.js {$file} → public/vendor/pdfjs/{$file}");
            $copied++;
        }

        $this->info("Synced {$copied} asset file(s).");

        return self::SUCCESS;
    }
}
