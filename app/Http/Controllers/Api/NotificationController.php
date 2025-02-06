<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends BaseController
{
    /**
     * Get notifications for the authenticated user.
     *
     * @return JsonResponse
     */
    public function getNotifications(): JsonResponse
    {
        try {
            $notifications = Auth::user()->notifications;

            return $this->sendSuccess($notifications, 'Notifications fetched successfully');
        } catch (\Exception $e) {
            Log::error('Fetching notifications failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while trying to fetch notifications. Please try again later.', 500);
        }
    }

    /**
     * Mark a specific notification as read.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function markAsRead($id): JsonResponse
    {
        try {
            $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
            $notification->update(['read' => true]);

            return $this->sendSuccess([], 'Notification marked as read');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Notification not found', ['error' => $e->getMessage(), 'notification_id' => $id]);
            return $this->sendError('Notification not found', 404);
        } catch (\Exception $e) {
            Log::error('Marking notification as read failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while trying to mark the notification as read. Please try again later.', 500);
        }
    }
}
