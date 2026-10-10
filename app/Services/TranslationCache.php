<?php

namespace App\Services;

use App\Models\Language;
use App\Models\TranslationKey;
use Illuminate\Support\Facades\Cache;

class TranslationCache
{
    /** Bump when loader/cache shape changes (e.g. nested groups). */
    private const VERSION = 2;

    public static function key(string $locale, string $group, ?int $revision = null): string
    {
        $revision ??= self::revision($locale);

        return 'app.translations.v'.self::VERSION.'.r'.$revision.".{$locale}.{$group}";
    }

    public static function revision(string $locale): int
    {
        return (int) Cache::get(self::revisionKey($locale), 1);
    }

    public static function forgetLocale(string $locale): void
    {
        $revision = self::revision($locale);
        $keysToForget = [];

        $groups = TranslationKey::query()->distinct()->pluck('group');

        foreach ($groups as $group) {
            foreach (self::groupAliases((string) $group) as $alias) {
                $keysToForget[] = self::key($locale, $alias, $revision);
                $keysToForget[] = 'app.translations.v'.self::VERSION.".{$locale}.{$alias}";
            }
        }

        Cache::forever(self::revisionKey($locale), $revision + 1);

        foreach (array_unique($keysToForget) as $cacheKey) {
            Cache::forget($cacheKey);
        }

        self::flushRuntimeTranslator();
    }

    public static function flushRuntimeTranslator(): void
    {
        if (! app()->resolved('translator')) {
            return;
        }

        $translator = app('translator');
        $loaded = new \ReflectionProperty($translator, 'loaded');
        $loaded->setValue($translator, []);
    }

    private static function revisionKey(string $locale): string
    {
        return 'app.translations.revision.'.$locale;
    }

    /** @return list<string> */
    private static function groupAliases(string $group): array
    {
        $aliases = [$group];

        if (str_contains($group, '.')) {
            $aliases[] = explode('.', $group)[0];
        }

        return array_values(array_unique($aliases));
    }

    public static function forgetAll(): void
    {
        Language::query()->pluck('code')->each(fn (string $code) => self::forgetLocale($code));
    }
}
