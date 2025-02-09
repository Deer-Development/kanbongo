<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Comment\ValidateCommentUpdate;
use App\Services\Comment\CommentService;
use App\Http\Resources\Comment\CommentResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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
        $comment = $this->service->update($id, $request->validated());

        // Handle temporary uploads and existing attachments
        if ($request->has('temporary_uploads')) {
            $currentAttachments = $comment->getMedia('attachments')->pluck('id')->toArray();
            $newUploads = collect($request->temporary_uploads)->pluck('id')->toArray();

            // Move temporary uploads to the comment
            foreach ($request->temporary_uploads as $temp) {
                $tempFile = Media::where('id', $temp['id'])->first();
                if ($tempFile) {
                    $tempFile->move($comment, 'attachments');
                }
            }

            // Delete attachments that are no longer in temporary_uploads
            $toDelete = array_diff($currentAttachments, $newUploads);
            Media::whereIn('id', $toDelete)->delete();
        }

        // Handle mentions
        if ($request->has('mentioned_users')) {
            $mentionedIds = collect($request->mentioned_users)
                ->map(fn($user) => $user['id'])
                ->toArray();
            $comment->mentions()->sync($mentionedIds);
        }

        $commentableType = $request->get('commentable_type');
        if (!class_exists($commentableType)) {
            return $this->errorResponse('Invalid commentable type.', Response::HTTP_BAD_REQUEST);
        }

        $commentable = $commentableType::with('comments.createdBy')->find($request->get('commentable_id'));

        if (!$commentable) {
            return $this->errorResponse('Commentable entity not found.', Response::HTTP_NOT_FOUND);
        }

        $comments = CommentResource::collection(
            $commentable->comments()->with('replies')->orderBy('created_at', 'ASC')->get()
        );

        return $this->successResponse($comments, 'Comment updated successfully.', Response::HTTP_OK);
    }
}
