<?php

namespace App\Services\Comment;

use App\Models\Comment;
use App\Services\BaseService;

class CommentService extends BaseService
{
    protected string $modelClass = Comment::class;

    // Define valid columns for sorting
    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }
}
