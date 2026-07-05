<?php

namespace App\Translation;

use App\Models\Language;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\FileLoader;

class DatabaseLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null): array
    {
        $lines = parent::load($locale, $group, $namespace);

        if ($namespace !== null || ! Schema::hasTable('translation_keys')) {
            return $lines;
        }

        $cacheKey = TranslationCache::key($locale, $group);

        if (Cache::has($cacheKey)) {
            $dbLines = Cache::get($cacheKey, []);
        } else {
            $dbLines = $this->loadFromDatabase($locale, $group);

            if ($dbLines !== []) {
                Cache::put($cacheKey, $dbLines, now()->addDay());
            }
        }

        return array_replace($lines, $dbLines);
    }

    protected function loadFromDatabase(string $locale, string $group): array
    {
        $languageId = Language::query()->where('code', $locale)->value('id');

        if (! $languageId) {
            return [];
        }

        return TranslationKey::query()
            ->where('group', $group)
            ->with(['translations' => fn ($query) => $query->where('language_id', $languageId)])
            ->get()
            ->mapWithKeys(function (TranslationKey $translationKey) {
                $value = $translationKey->translations->first()?->value;

                return [$translationKey->key => $value ?? $translationKey->key];
            })
            ->all();
    }
}
