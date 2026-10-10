<?php

use Database\Seeders\Data\TranslationCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        if (! class_exists(TranslationCatalog::class)) {
            return;
        }

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\TranslationSeeder',
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        //
    }
};
