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
use Illuminate\Support\Str;

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
        
        // Get task and its details
        $task = $comment->commentable;
        $board = $task->board;
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            Log::error('No authenticated user found when creating comment', [
                'comment_id' => $comment->id,
                'task_id' => $task->id
            ]);
            return $comment;
        }

        // Get author's full name or fallback
        $authorName = $currentUser->full_name ?? $currentUser->name ?? 'Someone';
        
        // Get comment preview for notification data
        $commentPreview = Str::limit(strip_tags($data['content'] ?? ''), 100);
        
        // Get mentioned users IDs
        $mentionedUserIds = collect($data['mentioned_users'] ?? [])->pluck('id')->toArray();

        // Get task members excluding mentioned users and current user
        $membersToNotify = $task->members()
            ->whereNotIn('user_id', array_merge($mentionedUserIds, [$currentUser->id]))
            ->get();

        // Notify task members (who are not mentioned)
        foreach ($membersToNotify as $member) {
            $this->createNotification(
                $member->user_id,
                'New Comment',
                sprintf('%s commented on Task #%d', $authorName, $task->sequence_id),
                'comment',
                $comment,
                [
                    'task_id' => $task->id,
                    'task_sequence_id' => $task->sequence_id ?? 0,
                    'task_name' => $task->name ?? 'Untitled Task',
                    'board_id' => $board->id ?? null,
                    'board_name' => $board->name ?? 'Unknown Board',
                    'container_id' => $task->container_id ?? null,
                    'container_name' => $task->container->name ?? 'Unknown Board',
                    'project_id' => $task->container->project_id ?? null,
                    'comment_id' => $comment->id,
                    'comment_preview' => $commentPreview,
                    'author' => [
                        'id' => $currentUser->id,
                        'name' => $authorName,
                        'avatar' => $currentUser->avatar ?? null,
                    ],
                    'action' => [
                        'text' => 'View Comment',
                        'icon' => 'tabler-message-circle',
                    ],
                    'context' => [
                        'emoji' => '💬',
                        'color' => 'primary',
                    ]
                ]
            );
        }

        // Handle mentions
        if (!empty($mentionedUserIds)) {
            foreach ($data['mentioned_users'] as $mentionedUser) {
                if ($mentionedUser['id'] !== $currentUser->id) {
                    $this->createNotification(
                        $mentionedUser['id'],
                        'Mention',
                        sprintf('%s mentioned you on Task #%d', $authorName, $task->sequence_id),
                        'mention',
                        $comment,
                        [
                            'task_id' => $task->id,
                            'task_sequence_id' => $task->sequence_id ?? 0,
                            'task_name' => $task->name ?? 'Untitled Task',
                            'board_id' => $board->id ?? null,
                            'board_name' => $board->name ?? 'Unknown Board',
                            'container_id' => $task->container_id ?? null,
                            'container_name' => $task->container->name ?? 'Unknown Board',
                            'project_id' => $task->container->project_id ?? null,
                            'comment_id' => $comment->id,
                            'comment_preview' => $commentPreview,
                            'author' => [
                                'id' => $currentUser->id,
                                'name' => $authorName,
                                'avatar' => $currentUser->avatar ?? null,
                            ],
                            'action' => [
                                'text' => 'Reply',
                                'icon' => 'tabler-message-plus',
                            ],
                            'context' => [
                                'emoji' => '👋',
                                'color' => 'info',
                            ]
                        ]
                    );
                }
            }
        }

        return $comment;
    }

    protected function createNotification(
        int $userId,
        string $title,
        string $content,
        string $type,
        Model $reference,
        array $data = []
    ): void {
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
                'data' => $data
            ]);

            Log::info('Broadcasting notification', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'type' => $type,
                'channel' => 'notifications.' . $userId
            ]);

            broadcast(new NewNotification($notification))->toOthers();
            
            DB::commit();

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
