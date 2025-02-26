<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends BaseController
{
    public function index(): JsonResponse
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return $this->successResponse(
            NotificationResource::collection($notifications)
        );
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        Auth::user()->markNotificationAsRead($notification);

        return $this->successResponse(
            new NotificationResource($notification)
        );
    }

    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $notification->delete();

        return $this->successResponse(null, 'Notification deleted successfully.');
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->markAllNotificationsAsRead();

        return $this->successResponse(null, 'All notifications marked as read.');
    }
} 