<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Task\ValidateTaskStore;
use App\Services\Task\TaskService;
use App\Http\Resources\Task\TaskResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTaskStore $request): JsonResponse
    {
        $model = $this->service->create($request->validated());

        return $this->successResponse(new TaskResource($model), 'Task created successfully.', Response::HTTP_CREATED);
    }
}
