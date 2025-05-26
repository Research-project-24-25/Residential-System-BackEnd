<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

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

        // Create required directories if they don't exist
        $directories = [
            'meeting-documents',
            'resident-images',
            'property-images',
            'maintenance-images'
        ];

        foreach ($directories as $directory) {
            $path = storage_path('app/public/' . $directory);
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }


        // Email Verification URL
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $expires = now()->addMinutes(Config::get('auth.verification.expire', 60));
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());

            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                $expires,
                compact('id', 'hash')
            );

            $query = parse_url($signedUrl, PHP_URL_QUERY);
            $params = [];
            if ($query) {
                parse_str($query, $params);
            }
            $queryParams = array_merge(
                $params,
                compact('id', 'hash')
            );

            return config('app.frontend_url') . '/verify-email?' . http_build_query($queryParams);
        });
    }
}
