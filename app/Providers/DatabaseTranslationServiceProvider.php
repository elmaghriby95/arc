<?php

namespace App\Providers;

use App\Translation\DatabaseLoader;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Translation\Translator;

class DatabaseTranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->bound('translation.loader')) {
            $this->app->register(TranslationServiceProvider::class);
        }

        $this->app->extend('translation.loader', function ($loader, $app) {
            return new DatabaseLoader($app['files'], [lang_path()]);
        });

        $this->app->extend('translator', function (Translator $translator, $app) {
            $loader = $app->make('translation.loader');

            if ($loader instanceof DatabaseLoader) {
                return $translator;
            }

            $replacement = new Translator(
                new DatabaseLoader($app['files'], [lang_path()]),
                $app->getLocale(),
            );

            $replacement->setFallback($app->getFallbackLocale());

            return $replacement;
        });
    }
}
