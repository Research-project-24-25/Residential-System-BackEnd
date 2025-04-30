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
            $perPage = $request->get('per_page', 10);
            $user = $request->user();

            $notifications = Notification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return NotificationResource::collection($notifications);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get all unread notifications for the authenticated user
     *
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function unread(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $user = $request->user();

            $notifications = Notification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->unread()
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return NotificationResource::collection($notifications);
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
     * Get notification count for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $total = Notification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->count();

            $unread = Notification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->unread()
                ->count();

            return $this->successResponse('Notification count retrieved successfully', [
                'total' => $total,
                'unread' => $unread
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
