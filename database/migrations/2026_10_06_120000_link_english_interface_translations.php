<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Database\Seeders\Data\TranslationCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('languages')
            || ! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')) {
            return;
        }

        $english = Language::query()->where('code', 'en')->first();

        if (! $english) {
            if (! Language::query()->exists()) {
                return;
            }

            $english = Language::query()->create([
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
            ]);
        }

        if (! $english->is_active || $english->direction !== 'ltr') {
            $english->forceFill([
                'is_active' => true,
                'direction' => 'ltr',
            ])->save();
        }

        foreach (TranslationCatalog::all() as $entry) {
            $value = $entry['values']['en'] ?? null;

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $translationKey = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
                ['description' => $entry['description'] ?? null],
            );

            $translation = Translation::query()->firstOrNew([
                'translation_key_id' => $translationKey->id,
                'language_id' => $english->id,
            ]);

            if (filled($translation->value)) {
                continue;
            }

            $translation->value = $value;
            $translation->save();
        }

        TranslationCache::forgetLocale('en');
    }

    public function down(): void
    {
        if (Schema::hasTable('languages')) {
            TranslationCache::forgetLocale('en');
        }
    }
};
