<?php

namespace App\Providers;

use App\Models\TransactionStatusHistory;
use App\Observers\TransactionStatusHistoryObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        \Illuminate\Pagination\Paginator::useBootstrapFive();

        TransactionStatusHistory::observe(TransactionStatusHistoryObserver::class);

        View::composer(['layouts.partials.navbar', 'layouts.login', 'layouts.guest', 'auth.login'], function ($view) {
            $view->with('navbarLanguages', \App\Models\Language::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get());
        });

        View::composer('layouts.partials.navbar', function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $view->with([
                'navbarNotifications' => $user->notifications()->limit(15)->get(),
                'navbarUnreadCount' => $user->unreadNotifications()->count(),
            ]);
        });

        View::composer('*', function ($view) {
            if (! isset($view->getData()['activeLanguage'])) {
                $view->with('activeLanguage', \App\Models\Language::query()
                    ->where('code', app()->getLocale())
                    ->first());
            }
        });

        \Illuminate\Support\Facades\Blade::if('permission', function (string ...$permissions) {
            $user = auth()->user();

            if (! $user) {
                return false;
            }

            foreach ($permissions as $permission) {
                if ($user->hasPermission($permission)) {
                    return true;
                }
            }

            return false;
        });
    }
}
