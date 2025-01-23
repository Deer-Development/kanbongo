<?php

namespace App\Http\Resources\Container;

namespace App\Http\Resources\Container;

use App\Http\Resources\Task\TaskResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ContainerResource extends JsonResource
{
    public function toArray($request)
    {
        $authUser = Auth::user();
        $isOwner = $authUser->id === $this->owner_id;
        $isMember = $this->members->contains('user_id', $authUser->id);
        $hasActiveTimeEntries = $this->boards->flatMap(function ($board) {
            return $board->tasks;
        })->flatMap(function ($task) {
            return $task->timeEntries;
        })->contains('end', null);
        $isSuperAdmin = $authUser->isSuperAdmin();

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'owner_id' => $this->owner_id,
            'owner' => $this->owner,
            'members' => $this->members,
            'boards' => $this->boards()->orderBy('order')->get()->map(function ($board) {
                return [
                    'id' => $board->id,
                    'container_id' => $board->container_id,
                    'name' => $board->name,
                    'description' => $board->description,
                    'is_active' => $board->is_active,
                    'order' => $board->order,
                    'color' => $board->color,
                    'created_at' => $board->created_at,
                    'updated_at' => $board->updated_at,
                    'deleted_at' => $board->deleted_at,
                    'members' => $board->members,
                    'tasks' => TaskResource::collection($board->tasks),
                ];
            }),
            'auth' => [
                'id' => $authUser->id,
                'is_owner' => $isOwner,
                'is_member' => $isMember,
                'has_active_time_entries' => $hasActiveTimeEntries,
                'is_super_admin' => $isSuperAdmin,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
