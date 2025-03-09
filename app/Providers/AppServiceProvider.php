<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Auth\Notifications\ResetPassword;
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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

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
