<?php

declare(strict_types=1);

/**
 * Compress scanned pages and assemble multi-page PDF.
 */
final class ScanPostProcessor
{
    public static function optimizeJpeg(string $path, int $quality = 48, int $maxWidth = 992): string
    {
        $powershell = self::resizeWithPowerShell($path, $maxWidth, $quality);

        if ($powershell !== null) {
            if ($powershell !== $path) {
                @unlink($path);
            }

            return $powershell;
        }

        if (! extension_loaded('gd')) {
            return $path;
        }

        self::ensureMemoryLimit('512M');

        $info = @getimagesize($path);
        $width = is_array($info) ? (int) $info[0] : 0;

        $image = self::loadImage($path);

        if ($image === null) {
            return $path;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newHeight = max(1, (int) round($height * ($maxWidth / $width)));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);

        $target = self::tempPath('jpg');
        imagejpeg($image, $target, max(35, min(75, $quality)));
        imagedestroy($image);

        if ($target !== $path) {
            @unlink($path);
        }

        return $target;
    }

    /** @param list<string> $jpegPaths */
    public static function buildPdf(array $jpegPaths, int $dpi = 120): string
    {
        if ($jpegPaths === []) {
            throw new RuntimeException('No pages to export.');
        }

        return PdfBuilder::fromJpegPages($jpegPaths, $dpi);
    }

    private static function resizeWithPowerShell(string $path, int $maxWidth, int $quality): ?string
    {
        $script = __DIR__.DIRECTORY_SEPARATOR.'resize-scan.ps1';

        if (! is_readable($script)) {
            return null;
        }

        $target = self::tempPath('jpg');
        $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
            .escapeshellarg($script).' '
            .escapeshellarg($path).' '
            .escapeshellarg($target).' '
            .$maxWidth.' '
            .$quality;

        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_readable($target) || (filesize($target) ?: 0) === 0) {
            @unlink($target);

            return null;
        }

        return $target;
    }

    private static function loadImage(string $path): ?\GdImage
    {
        $jpeg = @imagecreatefromjpeg($path);

        if ($jpeg instanceof \GdImage) {
            return $jpeg;
        }

        if (function_exists('imagecreatefrombmp')) {
            $bmp = @imagecreatefrombmp($path);

            if ($bmp instanceof \GdImage) {
                return $bmp;
            }
        }

        return null;
    }

    private static function ensureMemoryLimit(string $limit): void
    {
        $current = ini_get('memory_limit');

        if ($current === '-1') {
            return;
        }

        $currentBytes = self::memoryLimitToBytes($current);
        $targetBytes = self::memoryLimitToBytes($limit);

        if ($currentBytes < $targetBytes) {
            @ini_set('memory_limit', $limit);
        }
    }

    private static function memoryLimitToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private static function tempPath(string $extension): string
    {
        $directory = sys_get_temp_dir();

        do {
            $target = $directory.DIRECTORY_SEPARATOR.'arc-scan-'.bin2hex(random_bytes(8)).'.'.$extension;
        } while (file_exists($target));

        return $target;
    }
}
