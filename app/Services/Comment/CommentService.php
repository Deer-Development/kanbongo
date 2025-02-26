<?php

namespace App\Services\Comment;

use App\Models\Comment;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Events\NewNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    public function markMentionAsRead($mentionId, $userId): void
    {
        DB::table('mention_reads')->updateOrInsert([
            'mention_id' => $mentionId,
            'user_id' => $userId,
        ]);
    }

    public function create(array $data): Model
    {
        $comment = parent::create($data);
        
        // Notify assigned users
        $task = $comment->commentable;
        if ($task->assigned_to) {
            $this->createNotification(
                $task->assigned_to,
                'New Comment',
                "New comment on task: {$task->title}",
                'comment',
                $comment
            );
        }

        // Notify mentioned users
        if (isset($data['mentioned_users'])) {
            foreach ($data['mentioned_users'] as $user) {
                $this->createNotification(
                    $user['id'],
                    'Mention in Comment',
                    "You were mentioned in a comment",
                    'mention',
                    $comment
                );
            }
        }

        return $comment;
    }

    protected function createNotification(
        int $userId,
        string $title,
        string $content,
        string $type,
        Model $reference
    ): void
    {
        if ($userId === Auth::id()) {
            return;
        }

        try {
            DB::beginTransaction();

            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'content' => $content,
                'type' => $type,
                'reference_id' => $reference->id,
                'reference_type' => get_class($reference),
                'data' => [
                    'task_id' => $reference->commentable_id,
                    'comment_id' => $reference->id
                ]
            ]);

            Log::info('Broadcasting notification', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'type' => $type,
                'channel' => 'notifications.' . $userId
            ]);

            $event = new NewNotification($notification);
            $response = broadcast($event);

            Log::info('Broadcast response', [
                'response' => $response,
                'notification_id' => $notification->id
            ]);
            
            DB::commit();

            Log::info('Notification broadcasted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'type' => $type
            ]);
        }
    }
}
