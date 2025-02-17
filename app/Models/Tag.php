<?php

namespace App\Models;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use SoftDeletes, Filterable;

    protected $guarded = ['id'];

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }

    public function tasks()
    {
        return $this->morphedByMany(Task::class, 'taggable', 'taggables', 'tag_id', 'taggable_id');
    }

    public static function getAvailableColors(): array
    {
        return [
            '#ef5350', '#ec407a', '#ab47bc', '#7e57c2', '#42a5f5', '#26c6da',
            '#26a69a', '#66bb6a', '#ffee58', '#ffca28', '#ffa726', '#ff7043',
            '#8d6e63', '#78909c', '#e0e0e0', '#757575', '#cddc39', '#5c6bc0',
            '#29b6f6', '#9ccc65', '#388e3c', '#ff4081', '#ff5722', '#303f9f'
        ];
    }

    public function setRandomColor(): void
    {
        $this->color = collect(self::getAvailableColors())->random();
    }
}
