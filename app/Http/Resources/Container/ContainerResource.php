<?php

namespace App\Http\Resources\Container;

use App\Http\Resources\Task\TaskBoardResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\User\UserResource;
class ContainerResource extends JsonResource
{
    public function toArray($request): array
    {
        $authUser = Auth::user();
        $isOwner = $authUser->id === $this->owner_id;
        $isMember = $this->members->contains('user_id', $authUser->id);
        $isSuperAdmin = $authUser->isSuperAdmin();
        $startOfWeek = Carbon::now()->startOfWeek()->startOfDay()->toDateTimeString();
        $endOfWeek = Carbon::now()->endOfWeek()->endOfDay()->toDateTimeString();

        $taskTimeData = $this->timeEntries
            ->whereNotNull('end')
            ->groupBy('task_id')
            ->map(function ($entries) {
                $trackedTime = $entries->sum(fn($entry) => $entry->end
                    ? Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))
                    : 0);

                return [
                    'trackedTime' => $trackedTime,
                    'trackedTimeDisplay' => $this->formatTime($trackedTime),
                    'usersTracked' => $entries->pluck('user_id')->unique()->values()->all(),
                ];
            });

        $boards = $this->boards->map(function ($board) use ($taskTimeData) {
            $board->tasks->map(function ($task) use ($taskTimeData) {
                $task->tracked_time = $taskTimeData->get($task->id);
            });

            return [
                'id' => $board->id,
                'container_id' => $board->container_id,
                'name' => $board->name,
                'is_active' => $board->is_active,
                'order' => $board->order,
                'color' => $board->color,
                'created_at' => $board->created_at,
                'updated_at' => $board->updated_at,
                'deleted_at' => $board->deleted_at,
                'members' => $board->members,
                'tasks' => TaskBoardResource::collection($board->tasks),
            ];
        });

        $hasActiveTimeEntries = $this->timeEntries->where('user_id', $authUser->id)->whereNull('end')->isNotEmpty();

        $weeklyTrackedTime = $this->timeEntries
            ->whereNotNull('end')
            ->filter(function ($entry) use ($startOfWeek, $endOfWeek) {
                return
                    // Entry starts and ends within this week
                    ($entry->start >= $startOfWeek && $entry->end <= $endOfWeek) ||
                    // Entry started before this week but ended within this week
                    ($entry->start < $startOfWeek && $entry->end >= $startOfWeek) ||
                    // Entry started within this week but ends after this week
                    ($entry->start >= $startOfWeek && $entry->start <= $endOfWeek && $entry->end > $endOfWeek) ||
                    // Entry started before this week and ends after this week (spans across the week)
                    ($entry->start < $startOfWeek && $entry->end > $endOfWeek);
            })
            ->groupBy('user_id')
            ->map(function ($entries) use ($startOfWeek, $endOfWeek) {
                $totalTime = $entries->sum(function ($entry) use ($startOfWeek, $endOfWeek) {
                    $entryStart = Carbon::parse($entry->start);
                    $entryEnd = Carbon::parse($entry->end);

                    // Adjust the start time to the beginning of the week if it started earlier
                    if ($entryStart < $startOfWeek) {
                        $entryStart = Carbon::parse($startOfWeek);
                    }
                    // Adjust the end time to the end of the week if it extends beyond
                    if ($entryEnd > $endOfWeek) {
                        $entryEnd = Carbon::parse($endOfWeek);
                    }

                    // Calculate only the time worked within this week
                    return $entryStart->diffInSeconds($entryEnd);
                });

                return [
                    'total_seconds' => $totalTime,
                    'total_display' => $this->formatTime($totalTime),
                ];
            });

        $activeUsers = $this->timeEntries
            ->whereNull('end')
            ->map(function ($timeEntry) use ($weeklyTrackedTime) {
                $userWeeklyTime = $weeklyTrackedTime->get($timeEntry->user_id, ['total_seconds' => 0, 'total_display' => '00:00:00']);
                $weeklyLimitHours = $this->members->where('user_id', $timeEntry->user_id)->first()->weekly_limit_hours;
                $weeklyLimitSeconds = $this->members->where('user_id', $timeEntry->user_id)->first()->weekly_limit_hours * 3600;

                return [
                    'user' => $timeEntry->user,
                    'weekly_tracked' => $userWeeklyTime,
                    'has_weekly_limit' => $this->members->where('user_id', $timeEntry->user_id)->first()->weekly_limit_enabled,
                    'weekly_limit_hours' => $weeklyLimitHours,
                    'weekly_limit_seconds' => $weeklyLimitSeconds,
                    'time_entry' => [
                        'id' => $timeEntry->id,
                        'start' => $timeEntry->start,
                        'task_id' => $timeEntry->task_id,
                        'task_sequence_id' => $timeEntry->task?->sequence_id,
                        'task_deleted_at' => $timeEntry->task?->deleted_at,
                    ],
                ];
            });

        $inactiveUsers = $this->members
            ->whereNotIn('user_id', $activeUsers->pluck('user.id'))
            ->map(function ($member) use ($weeklyTrackedTime) {
                $lastTimeEntry = $this->timeEntries
                    ->where('user_id', $member->user_id)
                    ->sortByDesc('start')
                    ->first();

                $userWeeklyTime = $weeklyTrackedTime->get($member->user_id, ['total_seconds' => 0, 'total_display' => '00:00:00']);

                return [
                    'user' => $member->user,
                    'weekly_tracked' => $userWeeklyTime,
                    'has_weekly_limit' => $member->weekly_limit_enabled,
                    'weekly_limit_hours' => $member->weekly_limit_hours,
                    'last_time_entry' => $lastTimeEntry ? [
                        'id' => $lastTimeEntry->id,
                        'start' => $lastTimeEntry->start,
                        'end' => $lastTimeEntry->end,
                        'task_id' => $lastTimeEntry->task_id,
                        'task_sequence_id' => $lastTimeEntry->task?->sequence_id,
                        'task_deleted_at' => $lastTimeEntry->task?->deleted_at,
                    ] : null,
                ];
            })
            ->sortByDesc(function ($user) {
                return $user['last_time_entry']['end'] ?? null;
            })
            ->values();

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->project->name,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'owner_id' => $this->owner_id,
            'owner' => $this->owner,
            'members' => $this->members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'is_admin' => $member->is_admin,
                    'can_timing' => $member->can_timing,
                    'billable' => $member->billable,
                    'billable_rate' => $member->billable_rate,
                    'weekly_limit_enabled' => $member->weekly_limit_enabled,
                    'weekly_limit_hours' => $member->weekly_limit_hours,
                    'user' => array_merge(
                        $member->user->toArray(),
                        [
                            'role' => $member->user->roles->first()?->name,
                            'name' => $member->user->full_name,
                            'avatarOrInitials' => $member->user->avatar_or_initials,
                        ]
                    ),
                ];
            }),
            'boards' => $boards,
            'auth' => [
                'id' => $authUser->id,
                'is_owner' => $isOwner,
                'is_admin' => $this->members->where('user_id', $authUser->id)->first()?->is_admin,
                'is_member' => $isMember,
                'has_active_time_entries' => $hasActiveTimeEntries,
                'is_super_admin' => $isSuperAdmin,
                'weekly_tracked' => $weeklyTrackedTime->get($authUser->id, ['total_seconds' => 0, 'total_display' => '00:00:00']),
                'has_weekly_limit' => $this->members->where('user_id', $authUser->id)->first()?->weekly_limit_enabled,
                'weekly_limit_hours' => $this->members->where('user_id', $authUser->id)->first()?->weekly_limit_hours,
                'weekly_limit_seconds' => $this->members->where('user_id', $authUser->id)->first()?->weekly_limit_hours * 3600,
            ],
            'active_users' => $activeUsers->toArray(),
            'inactive_users' => $inactiveUsers->toArray(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    private function formatTime($seconds = 0): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
