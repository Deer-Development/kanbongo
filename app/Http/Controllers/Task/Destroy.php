<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Services\Task\TaskService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected TaskService $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $task = $this->service->getById($id);

        if ($task->deleted_at) {
            return $this->errorResponse('Task already deleted.', Response::HTTP_BAD_REQUEST);
        }

        if ($activeTimeEntries = $task->timeEntries()->whereNull('end')->get()) {
            $activeTimeEntries->each(function ($timeEntry) {
                $timeEntry->update(['end' => now(), 'stopped_by_system' => true]);
            });
        }

        $this->service->delete($id);

        return $this->successResponse([], 'Task deleted successfully.', Response::HTTP_OK);
    }
}
