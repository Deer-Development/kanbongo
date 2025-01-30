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
            'from_registration' => $this->from_registration,
            'avatar' => $this->avatar ?? null,
            'avatarOrInitials' => $this->avatar_or_initials,
            'role' => $this->roles->first()?->name,
            'invited_at' => $this->invited_at,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
