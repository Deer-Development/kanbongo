<?php

namespace App\Models;

use App\Enums\LogAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Log extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function getActionAttribute(string $value): LogAction
    {
        return LogAction::from($value);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }
}
