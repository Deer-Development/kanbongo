<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Services\Comment\CommentService;
use App\Http\Resources\Comment\CommentResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new CommentResource($model), 'Comment details fetched successfully.');
    }
}
