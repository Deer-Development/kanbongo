<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes, Filterable;

    protected $guarded = ['id'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_mentions', 'comment_id', 'user_id');
    }

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_reads', 'comment_id', 'user_id');
    }

    public function isReadByUser($userId): bool
    {
        return $this->readByUsers()->where('user_id', $userId)->exists();
    }
}
