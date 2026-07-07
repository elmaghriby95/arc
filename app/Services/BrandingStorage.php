<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandingStorage
{
    public const DIR = 'branding';

    public static function store(UploadedFile $file, string $prefix, ?string $oldPath = null): string
    {
        self::delete($oldPath);

        $directory = public_path(self::DIR);
        File::ensureDirectoryExists($directory, 0755, true);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $filename = $prefix.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(8)).'.'.$extension;

        $file->move($directory, $filename);

        $savedPath = $directory.DIRECTORY_SEPARATOR.$filename;

        if (! is_file($savedPath)) {
            throw new \RuntimeException('Failed to save branding file.');
        }

        @chmod($savedPath, 0644);

        return $filename;
    }

    public static function delete(?string $stored): void
    {
        $path = self::resolveFile($stored);

        if ($path) {
            File::delete($path);
        }
    }

    public static function url(?string $stored, ?int $version = null): ?string
    {
        if (! self::exists($stored)) {
            return null;
        }

        $filename = basename((string) $stored);
        $base = rtrim(request()->getBasePath(), '/');
        $url = ($base !== '' ? $base : '').'/brand/'.rawurlencode($filename);

        if ($version) {
            $url .= '?v='.$version;
        }

        return $url;
    }

    public static function exists(?string $stored): bool
    {
        return self::resolveFile($stored) !== null;
    }

    public static function resolveFile(?string $stored): ?string
    {
        if (! filled($stored)) {
            return null;
        }

        $filename = basename($stored);

        foreach (self::candidatePaths($stored, $filename) as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /** @return list<string> */
    private static function candidatePaths(string $stored, string $filename): array
    {
        return array_unique([
            public_path(self::DIR.DIRECTORY_SEPARATOR.$filename),
            public_path(str_replace('/', DIRECTORY_SEPARATOR, $stored)),
            storage_path('app/public/'.self::DIR.DIRECTORY_SEPARATOR.$filename),
            storage_path('app/public/'.str_replace('/', DIRECTORY_SEPARATOR, $stored)),
        ]);
    }

    public static function isPublicPath(string $path): bool
    {
        return str_starts_with($path, self::DIR.'/') || ! str_contains($path, '/');
    }
}
