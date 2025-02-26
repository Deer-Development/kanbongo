<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'data' => $this->data,
            'is_seen' => $this->is_seen,
            'created_at' => $this->created_at->diffForHumans(),
            'reference' => [
                'id' => $this->reference_id,
                'type' => $this->reference_type,
            ]
        ];
    }
} 