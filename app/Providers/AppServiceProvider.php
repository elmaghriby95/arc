<?php

namespace App\Providers;

use App\Models\LendingRequestHistory;
use App\Models\TransactionStatusHistory;
use App\Observers\LendingRequestHistoryObserver;
use App\Observers\TransactionStatusHistoryObserver;
use App\Services\TransactionStatusNotificationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('files', function () {
            return new \App\Filesystem\Filesystem;
        });
    }

    public function boot(): void
    {
        $this->ensureRuntimePathsAreWritable();

        Schema::defaultStringLength(191);

        \Illuminate\Pagination\Paginator::useBootstrapFive();

        TransactionStatusHistory::observe(TransactionStatusHistoryObserver::class);
        LendingRequestHistory::observe(LendingRequestHistoryObserver::class);

        View::composer(['layouts.partials.topbar', 'layouts.partials.navbar', 'layouts.login', 'layouts.guest', 'auth.login'], function ($view) {
            $view->with('navbarLanguages', \App\Models\Language::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get());
        });

        View::composer(['layouts.partials.topbar', 'layouts.partials.navbar'], function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $notificationService = app(TransactionStatusNotificationService::class);
            $visibleNotifications = $notificationService->filterVisible(
                $user,
                $user->notifications()->latest()->limit(80)->get(),
            );
            $visibleUnread = $notificationService->filterVisible(
                $user,
                $user->unreadNotifications()->latest()->limit(80)->get(),
            );

            $view->with([
                'navbarNotifications' => $visibleNotifications->take(15),
                'navbarUnreadCount' => $visibleUnread->count(),
            ]);
        });

        View::composer('*', function ($view) {
            if (! isset($view->getData()['activeLanguage'])) {
                $view->with('activeLanguage', \App\Models\Language::query()
                    ->where('code', app()->getLocale())
                    ->first());
            }

            if (! isset($view->getData()['systemSettings'])) {
                $view->with('systemSettings', \App\Models\SystemSetting::instance());
            }
        });

        $this->syncSessionLifetimeFromSettings();

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

    private function ensureRuntimePathsAreWritable(): void
    {
        $compiled = config('view.compiled');
        $candidates = [
            is_string($compiled) && $compiled !== '' ? $compiled : null,
            storage_path('framework/views'),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'arc-views',
        ];

        foreach ($candidates as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            if (! is_dir($path)) {
                @mkdir($path, 0775, true);
            }

            if (is_dir($path) && is_writable($path)) {
                config(['view.compiled' => $path]);
                break;
            }
        }

        $logDirectory = storage_path('logs');

        if (! is_dir($logDirectory)) {
            @mkdir($logDirectory, 0775, true);
        }

        if (! is_dir($logDirectory) || ! is_writable($logDirectory)) {
            config(['logging.default' => 'errorlog']);
        }
    }

    private function syncSessionLifetimeFromSettings(): void
    {
        try {
            if (! Schema::hasTable('system_settings')
                || ! Schema::hasColumn('system_settings', 'idle_timeout_minutes')) {
                return;
            }

            $minutes = \App\Models\SystemSetting::instance()->idleTimeoutMinutes();

            if ($minutes > 0) {
                config(['session.lifetime' => $minutes]);
            }
        } catch (\Throwable) {
            // Ignore during early boot / migrations.
        }
    }
}
