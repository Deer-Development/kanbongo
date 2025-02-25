<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'owner' => $this->owner,
            'created_at' => $this->created_at->diffForHumans(),
            'stats' => [
                'boards_count' => $this->containers()->count(),
                'total_tasks' => $this->containers()
                    ->withCount('boards')
                    ->get()
                    ->sum('boards_count'),
            ]
        ];
    }
}
