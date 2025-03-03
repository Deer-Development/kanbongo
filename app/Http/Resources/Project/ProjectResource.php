<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        $user = Auth::user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'owner' => $this->owner,
            'created_at' => $this->created_at->diffForHumans(),
            'stats' => [
                'boards_count' => $this->containers()->where(
                    function ($q) use ($user) {
                        if ($user->hasRole('Super-Admin')) {
                            $q->with(['members' => function ($q) {
                                $q->with('user');
                            }]);
                        } else {
                            $q->where('owner_id', $user->id)
                                ->orWhereHas('members', function ($q) use ($user) {
                                    $q->where('user_id', $user->id);
                                })->with(['members' => function ($q) {
                                    $q->with('user');
                                }]);
                        }
                    }
                    
                )->count(),
                'total_tasks' => $this->containers()
                    ->where(
                        function ($q) use ($user, $isSuperAdmin) {
                            if ($user->hasRole('Super-Admin')) {
                                $q->with(['members' => function ($q) {
                                    $q->with('user');
                                }]);
                            } else {
                                $q->where('owner_id', $user->id)
                                    ->orWhereHas('members', function ($q) use ($user) {
                                        $q->where('user_id', $user->id);
                                    })->with(['members' => function ($q) {
                                        $q->with('user');
                                    }]);
                            }
                        }
                        
                    )
                    ->withCount('tasks')
                    ->get()
                    ->sum('tasks_count'),
            ]
        ];
    }
}
