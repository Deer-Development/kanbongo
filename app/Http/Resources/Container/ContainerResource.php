<?php

namespace App\Http\Resources\Container;

namespace App\Http\Resources\Container;

use App\Http\Resources\Task\TaskBoardResource;
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
        $boards = $this->boards()->orderBy('order')->get()->map(function ($board) {
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
                'tasks' => TaskBoardResource::collection($board->tasks()->orderBy('order')->get()),
            ];
        });

        $hasActiveTimeEntries = $boards->flatMap(function ($board) {
            return collect($board['tasks']);
        })->some(function ($task) use ($authUser) {
            return collect($task->members)->contains(function ($member) use ($authUser) {
                return $member['user_id'] === $authUser->id &&$member->user->timeEntries->some(function ($timeEntry) {
                    return $timeEntry->end === null;
                });
            });
        });

        $activeUsers = $boards->flatMap(function ($board) {
            return collect($board['tasks']);
        })->flatMap(function ($task) {
            return collect($task->members);
        })->filter(function ($member) {
            return $member->user->timeEntries->some(function ($timeEntry) {
                return $timeEntry->end === null && $timeEntry->container_id == $this->id;
            });
        })->map(function ($member) {
            $user = $member->user;
            $user->active_time_entry = $user->timeEntries->filter(function ($timeEntry) {
                return $timeEntry->end == null && $timeEntry->container_id == $this->id;
            })->first();
            unset($user->timeEntries);
            return $user;
        })->unique();

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
