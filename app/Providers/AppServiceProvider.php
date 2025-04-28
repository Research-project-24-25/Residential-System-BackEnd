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

        // Create property images directory if it doesn't exist
        $propertyImagesPath = public_path('property-images');
        if (!File::exists($propertyImagesPath)) {
            File::makeDirectory($propertyImagesPath, 0755, true);
        }
    }
}
