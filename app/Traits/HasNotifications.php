<?php

namespace App\Traits;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasNotifications
{
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->where('is_seen', false);
    }

    public function readNotifications(): HasMany
    {
        return $this->notifications()->where('is_seen', true);
    }

    public function markAllNotificationsAsRead(): void
    {
        $this->notifications()->update(['is_seen' => true]);
    }

    public function markNotificationAsRead(Notification $notification): void
    {
        if ($notification->user_id === $this->id) {
            $notification->update(['is_seen' => true]);
        }
    }

    public function markNotificationAsUnread(Notification $notification): void
    {
        if ($notification->user_id === $this->id) {
            $notification->update(['is_seen' => false]);
        }
    }
} 