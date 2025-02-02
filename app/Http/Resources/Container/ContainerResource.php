<?php

namespace App\Http\Resources\Container;

namespace App\Http\Resources\Container;

use App\Http\Resources\Task\TaskBoardResource;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ContainerResource extends JsonResource
{
    public function toArray($request)
    {
        $authUser = Auth::user();
        $isOwner = $authUser->id === $this->owner_id;
        $isMember = $this->members->contains('user_id', $authUser->id);
        $isSuperAdmin = $authUser->isSuperAdmin();
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

        $activeUsers = $this->timeEntries
            ->whereNull('end')
            ->map(function ($timeEntry) {
                return [
                    'user' => $timeEntry->user,
                    'time_entry' => [
                        'id' => $timeEntry->id,
                        'start' => $timeEntry->start,
                        'task_id' => $timeEntry->task_id,
                    ],
                ];
            });

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'owner_id' => $this->owner_id,
            'owner' => $this->owner,
            'members' => $this->members,
            'boards' => $boards,
            'auth' => [
                'id' => $authUser->id,
                'is_owner' => $isOwner,
                'is_member' => $isMember,
                'has_active_time_entries' => $hasActiveTimeEntries,
                'is_super_admin' => $isSuperAdmin,
            ],
            'active_users' => $activeUsers->toArray(),
//            'task_time_data' => $taskTimeData,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    private function formatTime($seconds = 0)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
