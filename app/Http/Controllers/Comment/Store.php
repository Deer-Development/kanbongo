<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Comment\ValidateCommentStore;
use App\Services\Comment\CommentService;
use App\Http\Resources\Comment\CommentResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateCommentStore $request): JsonResponse
    {
        $comment = $this->service->create(array_merge($request->validated(), [
            'created_by' => auth()->id(),
        ]));

        if ($request->has('temporary_uploads')) {
            foreach ($request->temporary_uploads as $temp) {
                $tempFile = Media::where('id', $temp['id'])->first();

                if ($tempFile) {
                    $tempFile->move($comment, 'attachments');
                }
            }
        }

        if ($request->has('mentioned_users')) {
            $mentionedIds = collect(
                $request->mentioned_users
            )->map(function ($user) {
                return $user['id'];
            })->toArray();
            $comment->mentions()->sync($mentionedIds);
        }

        $this->service->markCommentAsRead($comment->id, auth()->id());

        $commentableType = $request->get('commentable_type');
        if (!class_exists($commentableType)) {
            return $this->errorResponse('Invalid commentable type.', Response::HTTP_BAD_REQUEST);
        }

        $commentable = $commentableType::with('comments.createdBy')->find($request->get('commentable_id'));

        if (!$commentable) {
            return $this->errorResponse('Commentable entity not found.', Response::HTTP_NOT_FOUND);
        }

        $comments = CommentResource::collection($commentable->comments()->with('replies')->orderBy('created_at', 'ASC')->get());

        return $this->successResponse($comments, 'Comment created successfully.', Response::HTTP_CREATED);
    }
}
