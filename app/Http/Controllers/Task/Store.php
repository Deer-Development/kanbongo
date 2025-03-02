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
        $task = $this->service->create($request->validated());

        // Înregistrăm activitatea cu toate detaliile necesare
        $task->recordActivity('created', [
            'attributes' => [
                'id' => $task->id,
                'sequence_id' => $task->sequence_id,
                'name' => $task->name
            ]
        ]);

        return $this->successResponse(new TaskResource($task), 'Task created successfully.', Response::HTTP_CREATED);
    }
}
