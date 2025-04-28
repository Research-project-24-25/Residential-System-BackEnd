<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

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
            'property-images',
            'resident-images'
        ];

        foreach ($directories as $directory) {
            $path = public_path($directory);
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }
}
