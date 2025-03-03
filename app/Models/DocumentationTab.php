<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationTab extends Model
{
    protected $fillable = [
        'container_id',
        'name',
        'content',
        'order'
    ];

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }
} 