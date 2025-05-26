<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class AdminNotificationService
{
  const CACHE_KEY = 'last_notified_admin_index';

  public function notifyAdmin(Notification $notification): void
  {
    $admins = Admin::where('role', 'admin')->get();

    if ($admins->isEmpty()) {
      // No admins to notify
      return;
    }

    $lastIndex = Cache::get(self::CACHE_KEY, -1);
    $nextIndex = ($lastIndex + 1) % $admins->count();

    $adminToNotify = $admins->get($nextIndex);

    $adminToNotify->notify($notification);

    Cache::put(self::CACHE_KEY, $nextIndex, now()->addDays(7)); // Store index for a week
  }
}
