<?php

namespace App\Http\Resources\Total;

use Illuminate\Http\Resources\Json\JsonResource;

class TotalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
