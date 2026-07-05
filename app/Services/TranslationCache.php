<?php

namespace App\Services;

use App\Models\Language;
use App\Models\TranslationKey;
use Illuminate\Support\Facades\Cache;

class TranslationCache
{
    public static function key(string $locale, string $group): string
    {
        return "app.translations.{$locale}.{$group}";
    }

    public static function forgetLocale(string $locale): void
    {
        $groups = TranslationKey::query()->distinct()->pluck('group');

        foreach ($groups as $group) {
            Cache::forget(self::key($locale, $group));
        }
    }

    public static function forgetAll(): void
    {
        Language::query()->pluck('code')->each(fn (string $code) => self::forgetLocale($code));
    }
}
