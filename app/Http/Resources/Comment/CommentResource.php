<?php

namespace App\Http\Resources\Comment;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at->diffForHumans(),
            'createdBy' => [
                'id' => $this->createdBy->id,
                'full_name' => $this->createdBy->full_name,
                'avatar' => $this->createdBy->avatar,
                'avatar_or_initials' => $this->createdBy->avatar_or_initials,
            ],
            'attachments' => $this->getMedia('attachments')->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getFullUrl(),
                'name' => $media->file_name,
                'size' => $media->human_readable_size,
            ]),
            'mentions' => $this->mentions->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->full_name,
                'is_read' => $this->isReadByUser($user->id),
            ]),
            'parent' => $this->parent ? [
                'id' => $this->parent->id,
                'content' => $this->parent->content,
                'created_at' => $this->parent->created_at->diffForHumans(),
                'createdBy' => [
                    'id' => $this->parent->createdBy->id,
                    'full_name' => $this->parent->createdBy->full_name,
                    'avatar' => $this->parent->createdBy->avatar,
                    'avatar_or_initials' => $this->parent->createdBy->avatar_or_initials,
                ],
            ] : null,
            'is_read' => $this->isReadByUser(auth()->id()),
            'replies' => self::collection($this->whenLoaded('replies')),
        ];
    }
}
