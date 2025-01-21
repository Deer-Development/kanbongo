<?php

namespace App\Models;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use SoftDeletes, Filterable;

    protected $guarded = ['id'];

    public function containers(): HasMany
    {
        return $this->hasMany(Container::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
