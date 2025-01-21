<?php

namespace App\Http\Resources\Comment;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'createdBy' => $this->createdBy,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
