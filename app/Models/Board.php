<?php

namespace App\Models;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use SoftDeletes, Filterable;

    protected $guarded = ['id'];

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function members(): MorphMany
    {
        return $this->morphMany(Member::class, 'memberable');
    }
}
