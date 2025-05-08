<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     *
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();

            $query = Notification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->latest();

            // Filter by unread if the query parameter is present and truthy
            if ($request->boolean('unread')) {
                $query->unread();
            }

            $notifications = $query->paginate(10);

            // Always provide the count of unread notifications
            $unread_count = Notification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->unread()
                ->count();

            return NotificationResource::collection($notifications)
                ->additional(['meta' => ['unread_count' => $unread_count]]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Mark a notification as read
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $notification = Notification::where('id', $id)
                ->where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->first();

            if (!$notification) {
                return $this->notFoundResponse('Notification not found');
            }

            $notification->markAsRead();

            return $this->successResponse('Notification marked as read');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Notification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->unread()
                ->update(['read_at' => now()]);

            return $this->successResponse('All notifications marked as read');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete a notification
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $notification = Notification::where('id', $id)
                ->where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->first();

            if (!$notification) {
                return $this->notFoundResponse('Notification not found');
            }

            $notification->delete();

            return $this->successResponse('Notification deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete all notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroyAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Notification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->delete();

            return $this->successResponse('All notifications deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
