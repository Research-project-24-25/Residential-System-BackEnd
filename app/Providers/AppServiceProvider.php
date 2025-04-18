<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define a super admin gate
        Gate::define('super-admin', function (Admin $admin) {
            return $admin->isSuperAdmin();
        });

        // Define admin gate
        Gate::define('admin', function (Admin $admin) {
            return $admin->isAdmin() || $admin->isSuperAdmin();
        });
    }
}
