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
        if ($user = $request->user()) {
            if ($user->language_id) {
                $code = Language::query()
                    ->where('id', $user->language_id)
                    ->where('is_active', true)
                    ->value('code');

                if ($code) {
                    return $code;
                }
            }
        }

        $sessionLocale = $request->session()->get('locale');

        if ($sessionLocale && Language::query()->where('code', $sessionLocale)->where('is_active', true)->exists()) {
            return $sessionLocale;
        }

        $default = Language::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('code');

        return $default ?? config('app.fallback_locale', 'en');
    }
}
