<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'can_timing' => $this->can_timing,
            'billable' => $this->billable,
            'billable_rate' => $this->billable_rate,
            'name' => $this->name,
            'user' => $this->user,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
