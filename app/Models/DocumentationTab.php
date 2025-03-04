<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentationVersion::class);
    }
} 