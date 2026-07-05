<?php

namespace App\Services;

use App\Models\Language;
use App\Models\TranslationKey;
use Illuminate\Support\Facades\Cache;

class TranslationCache
{
    /** Bump when loader/cache shape changes (e.g. nested groups). */
    private const VERSION = 2;

    public static function key(string $locale, string $group): string
    {
        return 'app.translations.v'.self::VERSION.".{$locale}.{$group}";
    }

    public static function forgetLocale(string $locale): void
    {
        $groups = TranslationKey::query()->distinct()->pluck('group');
        $keysToForget = [];

        foreach ($groups as $group) {
            $keysToForget[] = self::key($locale, $group);

            if (str_contains($group, '.')) {
                $keysToForget[] = self::key($locale, explode('.', $group)[0]);
            }
        }

        foreach (array_unique($keysToForget) as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    public static function forgetAll(): void
    {
        Language::query()->pluck('code')->each(fn (string $code) => self::forgetLocale($code));
    }
}
