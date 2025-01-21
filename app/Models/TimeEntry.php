<?php

namespace App\Models;
use App\Traits\Filterable;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use Filterable;

    protected $guarded = ['id'];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class, 'container_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TimeEntryLog::class);
    }
}
