<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use Database\Seeders\Data\TranslationCatalog;
use Illuminate\Database\Seeder;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('languages')) {
            $this->command?->error('جدول languages غير موجود. نفّذ migrations أولاً.');

            return;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('translation_keys')) {
            $this->command?->error('جداول الترجمات غير موجودة. نفّذ migration: 2026_07_05_120000_create_translations_tables.php');

            return;
        }

        $languages = Language::query()->pluck('id', 'code');

        if ($languages->isEmpty()) {
            $this->command?->error('لا توجد لغات في قاعدة البيانات. نفّذ أولاً: php artisan db:seed --class=ArchiveSeeder --force');

            return;
        }

        $missingCodes = array_diff(['ar', 'en', 'fr'], $languages->keys()->all());

        if ($missingCodes !== []) {
            $this->command?->warn('رموز لغات ناقصة: '.implode(', ', $missingCodes));
        }

        $keyCount = 0;
        $translationCount = 0;

        foreach (TranslationCatalog::all() as $entry) {
            $translationKey = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
                ['description' => $entry['description'] ?? null],
            );

            $keyCount++;

            foreach ($entry['values'] as $code => $value) {
                if (! isset($languages[$code])) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    ['translation_key_id' => $translationKey->id, 'language_id' => $languages[$code]],
                    ['value' => $value],
                );

                $translationCount++;
            }
        }

        $this->command?->info("تم: {$keyCount} مفتاح، {$translationCount} ترجمة.");
    }
}
