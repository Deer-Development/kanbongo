<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommentMention extends Model
{
    protected $guarded = ['id'];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mention_reads', 'mention_id', 'user_id');
    }

    public function isReadByUser($userId): bool
    {
        return $this->readByUsers()->where('user_id', $userId)->exists();
    }
}
