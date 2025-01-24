<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Comment\ValidateCommentUpdate;
use App\Services\Comment\CommentService;
use App\Http\Resources\Comment\CommentResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateCommentUpdate $request, int $id): JsonResponse
    {
        $model = $this->service->update($id, $request->validated());

        $commentableType = $request->get('commentable_type');
        if (!class_exists($commentableType)) {
            return $this->errorResponse('Invalid commentable type.', Response::HTTP_BAD_REQUEST);
        }

        $commentable = $commentableType::with('comments.createdBy')->find($request->get('commentable_id'));

        if (!$commentable) {
            return $this->errorResponse('Commentable entity not found.', Response::HTTP_NOT_FOUND);
        }

        $comments = $commentable->comments()->orderBy('created_at', 'ASC')->get()->map(function ($comment) {
            return [
                'id' => $comment->id,
                'createdBy' => $comment->createdBy->only(['id', 'full_name', 'email', 'avatar_or_initials', 'avatar']),
                'content' => $comment->content,
                'created_at' => $comment->created_at->diffForHumans(),
            ];
        });

        return $this->successResponse($comments, 'Comment updated successfully.', Response::HTTP_OK);
    }
}
