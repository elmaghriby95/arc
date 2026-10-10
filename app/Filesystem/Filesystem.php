<?php

namespace App\Filesystem;

use Illuminate\Filesystem\Filesystem as BaseFilesystem;
use RuntimeException;

class Filesystem extends BaseFilesystem
{
    /**
     * Replace a file without tempnam().
     *
     * PHP falls back to the system temp directory when it cannot use the
     * target directory, then emits a notice. Laravel turns that notice into
     * an ErrorException and every Blade render becomes a 500.
     */
    public function replace($path, $content, $mode = null)
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;
        $directory = dirname($path);

        if (! is_dir($directory)) {
            $this->makeDirectory($directory, 0775, true);
        }

        $tempPath = $directory.DIRECTORY_SEPARATOR.bin2hex(random_bytes(16)).'.tmp';

        try {
            $this->put($tempPath, $content, true);

            if (! is_null($mode)) {
                @chmod($tempPath, $mode);
            } else {
                @chmod($tempPath, 0777 - umask());
            }

            if (! rename($tempPath, $path)) {
                throw new RuntimeException("Unable to replace file [{$path}].");
            }
        } catch (\Throwable $exception) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            throw $exception;
        }
    }
}
