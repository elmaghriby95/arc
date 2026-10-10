<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<array{group: string, key: string, values: array<string, string>}> */
    private array $entries = [
        ['group' => 'settings.folders', 'key' => 'filter_name', 'values' => [
            'ar' => 'بحث باسم المجلد',
            'en' => 'Search by folder name',
            'fr' => 'Recherche par nom de dossier',
        ]],
        ['group' => 'settings.folders', 'key' => 'filter_name_placeholder', 'values' => [
            'ar' => 'اسم المجلد أو الموقع',
            'en' => 'Folder name or location',
            'fr' => 'Nom du dossier ou emplacement',
        ]],
        ['group' => 'settings.folders', 'key' => 'filter_unit', 'values' => [
            'ar' => 'الوحدة التنظيمية',
            'en' => 'Organizational unit',
            'fr' => 'Unité organisationnelle',
        ]],
        ['group' => 'settings.folders', 'key' => 'filter_unit_all', 'values' => [
            'ar' => 'كل الوحدات',
            'en' => 'All units',
            'fr' => 'Toutes les unités',
        ]],
        ['group' => 'settings.folders', 'key' => 'filter_empty', 'values' => [
            'ar' => 'لا توجد مجلدات مطابقة للبحث.',
            'en' => 'No folders match the search.',
            'fr' => 'Aucun dossier ne correspond à la recherche.',
        ]],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')
            || ! Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');

        foreach ($this->entries as $entry) {
            $key = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
            );

            foreach ($entry['values'] as $code => $value) {
                if (! isset($languages[$code])) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    ['translation_key_id' => $key->id, 'language_id' => $languages[$code]],
                    ['value' => $value],
                );
            }
        }

        TranslationCache::forgetAll();
    }

    public function down(): void
    {
        if (! Schema::hasTable('translation_keys')) {
            return;
        }

        foreach ($this->entries as $entry) {
            TranslationKey::query()
                ->where('group', $entry['group'])
                ->where('key', $entry['key'])
                ->delete();
        }

        if (Schema::hasTable('languages')) {
            TranslationCache::forgetAll();
        }
    }
};
