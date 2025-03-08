<?php

namespace App\Services;

use App\Models\User;
use App\Models\Activity;
use App\Models\Notification;
use App\Notifications\ActivityDigestNotification;
use App\Services\ActivityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ActivityDigestService
{
    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function sendActivityDigests(): void
    {
        $fourHoursAgo = Carbon::now()->subHours(4);
        
        // Get all recent activities and notifications
        $activities = Activity::with(['subject', 'causer'])
            ->where('created_at', '>=', $fourHoursAgo)
            ->get();

        $notifications = Notification::with('user')
            ->where('is_seen', false)
            ->where('created_at', '>=', $fourHoursAgo)
            ->get();

        // Group by user_id from properties.attributes.user_id
        $userActivities = [];
        foreach ($activities as $activity) {
            if (isset($activity->properties['user_id'])) {
                $userId = $activity->properties['user_id'];
                if (!isset($userActivities[$userId])) {
                    $userActivities[$userId] = [];
                }
                
                $description = $this->getActivityDescription($activity);
                if (!empty($description)) {
                    $userActivities[$userId][] = [
                        'id' => $activity->id,
                        'description' => $description,
                        'user' => [
                            'id' => $activity->causer->id,
                            'name' => $activity->causer->full_name,
                            'avatar' => $activity->causer->avatar,
                            'initials' => $activity->causer->avatar_or_initials,
                        ],
                        'subject' => [
                            'type' => $activity->subject_type,
                            'id' => $activity->subject_id,
                            'name' => $activity->subject->name ?? null,
                        ],
                        'event' => $activity->event,
                        'properties' => $activity->properties,
                        'created_at' => $activity->created_at->diffForHumans(),
                        'created_at_formatted' => $activity->created_at->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        // Group notifications by user
        $userNotifications = [];
        foreach ($notifications as $notification) {
            $userId = $notification->user_id;
            if (!isset($userNotifications[$userId])) {
                $userNotifications[$userId] = [];
            }
            
            $userNotifications[$userId][] = [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at->diffForHumans(),
                'created_at_formatted' => $notification->created_at->format('Y-m-d H:i:s')
            ];
        }

        // Combine activities and notifications and send digests
        $userIds = array_unique(array_merge(array_keys($userActivities), array_keys($userNotifications)));
        foreach ($userIds as $userId) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $digestData = [
                        'activities' => $userActivities[$userId] ?? [],
                        'notifications' => $userNotifications[$userId] ?? [],
                        'summary' => [
                            'activity_count' => count($userActivities[$userId] ?? []),
                            'notification_count' => count($userNotifications[$userId] ?? [])
                        ]
                    ];
                    
                    if (!empty($digestData['activities']) || !empty($digestData['notifications'])) {
                        $user->notify(new ActivityDigestNotification($digestData));
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to send activity digest to user {$userId}: " . $e->getMessage());
            }
        }
    }

    private function getTargetUserIds($activity): array
    {
        $targetUserIds = [];

        // Check properties->attributes->user_id
        if (isset($activity['properties']['user_id'])) {
            $targetUserIds[] = $activity['properties']['user_id'];
        }

        // Check properties->user_id
        if (isset($activity['properties']['user_id'])) {
            $targetUserIds[] = $activity['properties']['user_id'];
        }

        // For task-related activities, include task members
        if ($activity['subject']['type'] === 'App\\Models\\Task') {
            // Get task members through the task's container
            if ($task = \App\Models\Task::with('board.container.members')->find($activity['subject']['id'])) {
                $memberIds = $task->board->container->members->pluck('user_id')->toArray();
                $targetUserIds = array_merge($targetUserIds, $memberIds);
            }
        }

        return array_unique($targetUserIds);
    }

    protected function getActivityDescription($activity): string
    {
        $userName = $activity['user']['full_name'];
        
        $taskBadge = '';
        if ($activity['subject_type'] === 'App\\Models\\Task') {
            $sequenceId = $activity['properties']['attributes']['sequence_id'] 
                ?? $activity['subject_id']
                ?? null;
            $taskBadge = $sequenceId ? "Task #{$sequenceId}" : '';
        }

        switch ($activity['event']) {
            case 'created':
                return "{$userName} created" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'updated':
                if (empty($activity['properties'])) {
                    return '';
                }
                
                if (isset($activity['properties']['name'])) {
                    $oldName = $activity['properties']['old']['name'] ?? '';
                    $newName = $activity['properties']['name'];
                    return "{$userName} renamed from \"{$oldName}\" to \"{$newName}\"";
                }
                
                return "{$userName} updated" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'deleted':
                return "{$userName} deleted" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'member_added':
                return "{$userName} added you to" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'member_removed':
                return "{$userName} removed you from" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'time_entry_completed':
                $duration = abs($activity['properties']['duration']);
                $timeFormatted = $this->formatDuration($duration);
                $addedManually = isset($activity['properties']['added_manually']) ? ' manually' : '';
                return "{$userName} tracked{$addedManually} {$timeFormatted} on" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'time_entry_deleted':
                $duration = abs($activity['properties']['duration']);
                $timeFormatted = $this->formatDuration($duration);
                $timeEntryUser = optional(User::find($activity['properties']['user_id']))->full_name;
                return "{$userName} deleted {$timeEntryUser}'s time entry of {$timeFormatted} from" . 
                       ($taskBadge ? " {$taskBadge}" : '');
            
            case 'time_entry_updated':
                $oldDuration = abs($activity['properties']['old_duration']);
                $newDuration = abs($activity['properties']['new_duration']);
                $oldTimeFormatted = $this->formatDuration($oldDuration);
                $newTimeFormatted = $this->formatDuration($newDuration);
                $timeEntryUser = optional(User::find($activity['properties']['user_id']))->full_name;
                return "{$userName} updated {$timeEntryUser}'s time entry from {$oldTimeFormatted} to {$newTimeFormatted} on" . 
                       ($taskBadge ? " {$taskBadge}" : '');
            
            case 'task_moved':
                return "{$userName} moved {$taskBadge}";
            
            default:
                return "{$userName} performed {$activity['event']} on" . ($taskBadge ? " {$taskBadge}" : '');
        }
    }

    private function formatDuration(int $duration): string
    {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        
        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $seconds
        );
    }
} 