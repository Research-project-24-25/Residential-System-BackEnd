<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request)
    {
        try {
            $query = Notification::where('user_email', $request->user()->email)->query();

            // Filter by read/unread
            if ($request->has('read')) {
                if ($request->read === '1' || $request->read === 'true') {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            }

            // Apply search, sorting and pagination
            $query = $this->applySearch($query, $request, ['title', 'message', 'type']);
            $query = $this->applySorting($query, $request);
            $notifications = $this->applyPagination($query, $request);

            return $this->successResponse('Notifications retrieved successfully', $notifications);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Show a specific notification.
     */
    public function show(string $id)
    {
        try {
            $notification = Notification::findOrFail($id);

            // Check if this notification belongs to the authenticated user
            if ($notification->user_email !== Auth::user()->email) {
                return $this->forbiddenResponse('You are not authorized to view this notification');
            }

            return $this->successResponse('Notification retrieved successfully', $notification);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id)
    {
        try {
            $notification = Notification::findOrFail($id);

            // Check if this notification belongs to the authenticated user
            if ($notification->user_email !== Auth::user()->email) {
                return $this->forbiddenResponse('You are not authorized to modify this notification');
            }

            $notification->markAsRead();

            return $this->successResponse('Notification marked as read successfully', $notification);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $count = Notification::where('user_email', $request->user()->email)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return $this->successResponse("$count notifications marked as read successfully");
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request)
    {
        try {
            $count = Notification::where('user_email', $request->user()->email)
                ->whereNull('read_at')
                ->count();

            return $this->successResponse('Unread notification count retrieved successfully', [
                'count' => $count
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
