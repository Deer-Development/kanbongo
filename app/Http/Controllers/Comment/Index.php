<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Services\Comment\CommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Comment\CommentResource;

class Index extends BaseController
{
    protected CommentService $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => CommentResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'Comment list retrieved successfully.');
    }
}
