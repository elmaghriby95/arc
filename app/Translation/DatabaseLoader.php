<?php

namespace App\Translation;

use App\Models\Language;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\FileLoader;

class DatabaseLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null): array
    {
        $lines = parent::load($locale, $group, $namespace);

        if ($this->shouldSkipDatabaseLoad($group, $namespace)
            || ! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')) {
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

        return array_replace_recursive($lines, $dbLines);
    }

    protected function shouldSkipDatabaseLoad(string $group, ?string $namespace): bool
    {
        if ($group === '*' && $namespace === '*') {
            return true;
        }

        return $namespace !== null && $namespace !== '*';
    }

    protected function loadFromDatabase(string $locale, string $group): array
    {
        $languageId = Language::query()->where('code', $locale)->value('id');

        if (! $languageId) {
            return [];
        }

        $lines = [];

        $translationKeys = TranslationKey::query()
            ->where(function ($query) use ($group) {
                $query->where('group', $group)
                    ->orWhere('group', 'like', $group.'.%');
            })
            ->with(['translations' => fn ($query) => $query->where('language_id', $languageId)])
            ->get();

        foreach ($translationKeys as $translationKey) {
            $value = $translationKey->translations->first()?->value;

            if ($value === null) {
                continue;
            }

            if ($translationKey->group === $group) {
                Arr::set($lines, $translationKey->key, $value);
            } else {
                $nestedKey = substr($translationKey->group, strlen($group) + 1).'.'.$translationKey->key;
                Arr::set($lines, $nestedKey, $value);
            }
        }

        return $lines;
    }
}
