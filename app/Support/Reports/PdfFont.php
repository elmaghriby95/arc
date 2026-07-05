<?php

namespace App\Support\Reports;

class PdfFont
{
    private const REMOTE_REGULAR = 'https://github.com/googlefonts/cairo/raw/main/fonts/ttf/Cairo-Regular.ttf';

    private const REMOTE_BOLD = 'https://github.com/googlefonts/cairo/raw/main/fonts/ttf/Cairo-Bold.ttf';

    public static function cairoRegular(): string
    {
        return self::resolve('Cairo-Regular.ttf', self::REMOTE_REGULAR);
    }

    public static function cairoBold(): string
    {
        return self::resolve('Cairo-Bold.ttf', self::REMOTE_BOLD);
    }

    public static function usesRemote(): bool
    {
        return ! is_readable(resource_path('fonts/cairo/Cairo-Regular.ttf'));
    }

    private static function resolve(string $filename, string $remoteUrl): string
    {
        $local = resource_path('fonts/cairo/'.$filename);

        if (is_readable($local)) {
            return 'resources/fonts/cairo/'.$filename;
        }

        return $remoteUrl;
    }
}
