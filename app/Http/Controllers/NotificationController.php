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
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total()
            ]
        ]);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $notification->update(['is_seen' => true]);

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
        Notification::where('user_id', Auth::id())
            ->update(['is_seen' => true]);

        return $this->successResponse(null, 'All notifications marked as read.');
    }

    public function markAsUnread(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $notification->update(['is_seen' => false]);
        
        return $this->successResponse(
            new NotificationResource($notification),
            'Notification marked as unread successfully'
        );
    }
} 