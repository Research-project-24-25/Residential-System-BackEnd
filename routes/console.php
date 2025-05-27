<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

Artisan::command('users:cleanup-expired-accounts', function () {
    $gracePeriodDays = config('auth.account_deletion_grace_period', 30);
    $cutoffDate = now()->subDays($gracePeriodDays);

    $expiredUsers = User::onlyTrashed()
        ->where('deleted_at', '<', $cutoffDate)
        ->get();

    $count = $expiredUsers->count();

    if ($count === 0) {
        $this->info('No expired user accounts found.');
        return 0;
    }

    foreach ($expiredUsers as $user) {
        // Force delete the user permanently
        $user->forceDelete();
    }

    $this->info("Permanently deleted {$count} expired user account(s).");

    return 0;
})->purpose('Permanently delete user accounts that have exceeded the grace period');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
