<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        $language = Language::query()
            ->where('code', $locale)
            ->where('is_active', true)
            ->first()
            ?? Language::query()->where('is_default', true)->first();

        View::share('activeLanguage', $language);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $sessionLocale = $request->session()->get('locale');

        if (is_string($sessionLocale) && $this->activeLocale($sessionLocale)) {
            return $sessionLocale;
        }

        if ($user = $request->user()) {
            if ($user->language_id) {
                $code = Language::query()
                    ->where('id', $user->language_id)
                    ->where('is_active', true)
                    ->value('code');

                if (is_string($code) && $code !== '') {
                    $request->session()->put('locale', $code);

                    return $code;
                }
            }
        }

        $default = Language::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('code');

        return $default ?? config('app.fallback_locale', 'en');
    }

    protected function activeLocale(string $code): bool
    {
        return Language::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->exists();
    }
}
