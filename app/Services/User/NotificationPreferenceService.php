<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\User;
use App\Models\NotificationPreference;
use Illuminate\Support\Carbon;

class NotificationPreferenceService
{
    public function updatePreferences(User $user, array $data): NotificationPreference
    {
        $preferences = $user->notificationPreferences()->updateOrCreate(
            ['user_id' => $user->id, 'daily_report_time' => Carbon::parse($data['daily_report_time'])->format('H:i:s')],
            $data
        );

        return $preferences->fresh();
    }

    public function getPreferences(User $user): NotificationPreference
    {
        return $user->notificationPreferences()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'activities_enabled' => true,
                'activities_frequency' => 'daily',
                'member_report_enabled' => true,
                'owner_report_enabled' => false,
                'daily_report_time' => '00:00:00',
            ]
        );
    }
} 