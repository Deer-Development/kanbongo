<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Services\Task\TaskService;
use App\Http\Resources\Task\TaskResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new TaskResource($model), 'Task details fetched successfully.');
    }
}
