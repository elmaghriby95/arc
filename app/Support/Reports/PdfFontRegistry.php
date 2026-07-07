<?php

namespace App\Support\Reports;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class PdfFontRegistry
{
    public const FAMILY = 'cairo';

    public const FALLBACK_FAMILY = 'dejavu sans';

    private const REGULAR_URL = 'https://github.com/googlefonts/cairo/raw/main/fonts/ttf/Cairo-Regular.ttf';

    private const BOLD_URL = 'https://github.com/googlefonts/cairo/raw/main/fonts/ttf/Cairo-Bold.ttf';

    public function familyForPdf(Dompdf $dompdf): string
    {
        return $this->ensureCairoRegistered($dompdf)
            ? self::FAMILY
            : self::FALLBACK_FAMILY;
    }

    public function ensureCairoRegistered(Dompdf $dompdf): bool
    {
        $fontMetrics = $dompdf->getFontMetrics();
        $families = $fontMetrics->getFontFamilies();

        if (isset($families[self::FAMILY]['normal'])) {
            return true;
        }

        $regular = resource_path('fonts/cairo/Cairo-Regular.ttf');
        $bold = resource_path('fonts/cairo/Cairo-Bold.ttf');

        if (! is_readable($regular)) {
            return false;
        }

        if (! $fontMetrics->registerFont(
            ['family' => self::FAMILY, 'weight' => 'normal', 'style' => 'normal'],
            $this->fileUri($regular)
        )) {
            return false;
        }

        if (is_readable($bold)) {
            $fontMetrics->registerFont(
                ['family' => self::FAMILY, 'weight' => 'bold', 'style' => 'normal'],
                $this->fileUri($bold)
            );
        }

        return isset($fontMetrics->getFontFamilies()[self::FAMILY]['normal']);
    }

    public function installFonts(): bool
    {
        File::ensureDirectoryExists(resource_path('fonts/cairo'));
        File::ensureDirectoryExists(storage_path('fonts'));

        if (! $this->cairoFontsInstalled() && ! $this->downloadFonts()) {
            return false;
        }

        return $this->registerInstalledFonts();
    }

    public function registerInstalledFonts(): bool
    {
        if (! $this->cairoFontsInstalled()) {
            return false;
        }

        File::ensureDirectoryExists(storage_path('fonts'));

        return $this->ensureCairoRegistered(app(Dompdf::class));
    }

    private function downloadFonts(): bool
    {
        foreach ([self::REGULAR_URL => 'Cairo-Regular.ttf', self::BOLD_URL => 'Cairo-Bold.ttf'] as $url => $filename) {
            $path = resource_path('fonts/cairo/'.$filename);

            if (is_readable($path) && filesize($path) > 1000) {
                continue;
            }

            $response = Http::timeout(120)->get($url);

            if (! $response->successful()) {
                return false;
            }

            File::put($path, $response->body());
        }

        return true;
    }

    public function cairoFontsInstalled(): bool
    {
        return is_readable(resource_path('fonts/cairo/Cairo-Regular.ttf'));
    }

    private function fileUri(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\\//', $path) === 1) {
            return 'file:///'.$path;
        }

        return 'file://'.$path;
    }
}
