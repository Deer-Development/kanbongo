<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Services\Comment\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkAsRead extends BaseController
{
    protected $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $type = $request->get('type');
        $id = $request->get('id');

        if ($type === 'comment') {
            $this->service->markCommentAsRead($id, $userId);
        } elseif ($type === 'mention') {
            $this->service->markMentionAsRead($id, $userId);
        } else {
            return $this->errorResponse('Invalid type provided.', Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse(null, 'Marked as read.', Response::HTTP_OK);
    }
}
