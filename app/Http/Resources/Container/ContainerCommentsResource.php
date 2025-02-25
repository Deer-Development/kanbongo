<?php

namespace App\Http\Resources\Container;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Comment\CommentResource;

class ContainerCommentsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'comments' => CommentResource::collection($this->comments()->with('replies')->orderBy('created_at', 'ASC')->get()),
        ];
    }
}
