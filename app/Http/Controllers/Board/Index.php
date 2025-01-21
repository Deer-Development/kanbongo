<?php

namespace App\Http\Controllers\Board;

use App\Http\Controllers\BaseController;
use App\Services\Board\BoardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Board\BoardResource;

class Index extends BaseController
{
    protected BoardService $service;

    public function __construct(BoardService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => BoardResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'Board list retrieved successfully.');
    }
}
