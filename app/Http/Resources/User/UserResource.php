<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'avatar' => $this->avatar ?? null,
            'avatarOrInitials' => $this->avatar_or_initials,
            'created_at' => $this->created_at->diffForHumans(),
            'invited_at' => $this->invited_at?->diffForHumans(),
        ];
    }
}
