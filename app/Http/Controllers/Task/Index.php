<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Task\TaskResource;

class Index extends BaseController
{
    protected TaskService $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => TaskResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'Task list retrieved successfully.');
    }
}
