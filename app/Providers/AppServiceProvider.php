<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
