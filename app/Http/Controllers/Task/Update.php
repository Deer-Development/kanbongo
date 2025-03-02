<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Task\ValidateTaskUpdate;
use App\Services\Task\TaskService;
use App\Http\Resources\Task\TaskResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTaskUpdate $request, int $id): JsonResponse
    {
        $task = $this->service->update($id, $request->validated());

        // Înregistrăm activitatea doar dacă s-a modificat numele
        if ($task->wasChanged('name')) {
            $task->recordActivity('updated', [
                'attributes' => [
                    'sequence_id' => $task->sequence_id,
                    'name' => $task->name
                ],
                'old' => [
                    'name' => $task->getOriginal('name')
                ]
            ]);
        }

        return $this->successResponse(new TaskResource($task), 'Task updated successfully.', Response::HTTP_OK);
    }
}
