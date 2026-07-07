<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandingStorage
{
    public const PUBLIC_DIR = 'branding';

    public static function store(UploadedFile $file, string $prefix, ?string $oldPath = null): string
    {
        self::delete($oldPath);

        $directory = public_path(self::PUBLIC_DIR);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $filename = $prefix.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(8)).'.'.$extension;

        $file->move($directory, $filename);

        return self::PUBLIC_DIR.'/'.$filename;
    }

    public static function delete(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (self::isPublicPath($path)) {
            File::delete(public_path($path));

            return;
        }

        Storage::disk('public')->delete($path);
    }

    public static function url(?string $path, ?int $version = null): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $url = self::isPublicPath($path)
            ? asset($path)
            : asset('storage/'.$path);

        if ($version) {
            $url .= '?v='.$version;
        }

        return $url;
    }

    public static function exists(?string $path): bool
    {
        if (! filled($path)) {
            return false;
        }

        if (self::isPublicPath($path)) {
            return File::exists(public_path($path));
        }

        return Storage::disk('public')->exists($path);
    }

    public static function isPublicPath(string $path): bool
    {
        return str_starts_with($path, self::PUBLIC_DIR.'/');
    }
}
