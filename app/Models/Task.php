<?php

namespace App\Models;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Task extends Model
{
    use SoftDeletes, Filterable;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($task) {
            Log::create([
                'loggable_type' => self::class,
                'loggable_id' => $task->id,
                'user_id' => Auth::user()->id,
                'action' => 'delete',
                'task_id' => $task->id,
                'container_id' => $task->board->container_id,
                'old_data' => $task->toArray(),
                'new_data' => null,
            ]);
        });
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function members(): MorphMany
    {
        return $this->morphMany(Member::class, 'memberable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'task_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
