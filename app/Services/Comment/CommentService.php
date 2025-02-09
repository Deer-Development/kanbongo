<?php

namespace App\Services\Comment;

use App\Models\Comment;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class CommentService extends BaseService
{
    protected string $modelClass = Comment::class;

    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    public function markCommentAsRead($commentId, $userId = null): void
    {
        DB::table('comment_reads')->updateOrInsert([
            'comment_id' => $commentId,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    public function markMentionAsRead($mentionId, $userId): void
    {
        DB::table('mention_reads')->updateOrInsert([
            'mention_id' => $mentionId,
            'user_id' => $userId,
        ]);
    }
}
