<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Services\Comment\CommentService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected CommentService $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'Comment deleted successfully.', Response::HTTP_OK);
    }
}
