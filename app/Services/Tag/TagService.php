<?php

namespace App\Services\Tag;

use App\Models\Tag;
use App\Services\BaseService;

class TagService extends BaseService
{
    protected string $modelClass = Tag::class;

    // Define valid columns for sorting
    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }
}
