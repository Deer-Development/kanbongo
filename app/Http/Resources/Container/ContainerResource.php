<?php

namespace App\Http\Resources\Container;

namespace App\Http\Resources\Container;

use App\Http\Resources\Task\TaskBoardResource;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ContainerResource extends JsonResource
{
    public function toArray($request)
    {
        $authUser = Auth::user();
        $isOwner = $authUser->id === $this->owner_id;
        $isMember = $this->members->contains('user_id', $authUser->id);
        $isSuperAdmin = $authUser->isSuperAdmin();
        $boards = $this->boards->map(function ($board) {
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

        $hasActiveTimeEntries = $boards->flatMap(fn($board) => $board['tasks'])
            ->flatMap(fn($task) => $task->members)
            ->pluck('user.timeEntries')
            ->flatten()
            ->contains(fn($timeEntry) => $timeEntry->end === null);


        $taskIds = $this->boards->flatMap(fn($board) => $board->tasks)->pluck('id')->toArray();

        $activeUserIds = TimeEntry::whereNull('end')
            ->whereIn('task_id', $taskIds)
            ->pluck('user_id')
            ->unique();

        $activeUsers = User::whereIn('id', $activeUserIds)
            ->get()
            ->map(function ($user) use ($taskIds) {
                $user->active_time_entry = TimeEntry::where('user_id', $user->id)
                    ->whereIn('task_id', $taskIds)
                    ->whereNull('end')
                    ->select(['id', 'start', 'task_id'])
                    ->first();
                return $user;
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
            'active_users' => $activeUsers,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
