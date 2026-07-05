<?php

use Database\Seeders\TranslationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new TranslationSeeder)->run();

        $defaultLanguageId = \App\Models\Language::query()
            ->where('is_default', true)
            ->value('id');

        if ($defaultLanguageId) {
            \App\Models\User::query()
                ->whereNull('language_id')
                ->update(['language_id' => $defaultLanguageId]);
        }
    }

    public function down(): void
    {
        // Translation seed data is not reverted.
    }
};
