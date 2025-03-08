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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'is_active' => $this->is_active,
            'role' => $this->roles->first()?->name,
            'invited_at' => $this->invited_at,
            'is_temporary' => $this->is_temporary,
            'from_registration' => $this->from_registration,
            'email' => $this->email,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'avatar' => $this->avatar ?? null,
            'avatarOrInitials' => $this->avatar_or_initials,
            'avatar_or_initials' => $this->avatar_or_initials,
            'full_name' => $this->full_name,
            'isSuperAdmin' => $this->hasRole('Super-Admin'),
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at,
        ];
    }
}
