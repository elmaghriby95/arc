<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('system_settings')) {
            return;
        }

        $settings = DB::table('system_settings')->first();

        if (! $settings) {
            return;
        }

        $updates = [];

        foreach (['logo_path', 'favicon_path'] as $column) {
            $path = $settings->{$column};

            if (! filled($path)) {
                continue;
            }

            $filename = basename($path);
            $target = public_path('branding'.DIRECTORY_SEPARATOR.$filename);

            if (! is_file($target)) {
                $legacyPaths = [
                    public_path(str_replace('/', DIRECTORY_SEPARATOR, $path)),
                    storage_path('app/public/'.str_replace('/', DIRECTORY_SEPARATOR, $path)),
                    storage_path('app/public/branding'.DIRECTORY_SEPARATOR.$filename),
                ];

                foreach ($legacyPaths as $legacyPath) {
                    if (is_file($legacyPath)) {
                        File::ensureDirectoryExists(public_path('branding'), 0755, true);
                        File::copy($legacyPath, $target);
                        break;
                    }
                }
            }

            $updates[$column] = $filename;
        }

        if ($updates !== []) {
            DB::table('system_settings')->where('id', $settings->id)->update($updates);
        }
    }

    public function down(): void
    {
        // Paths are not reverted.
    }
};
