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

        return $this->successResponse(new CommentResource($model), 'Comment updated successfully.', Response::HTTP_OK);
    }
}
