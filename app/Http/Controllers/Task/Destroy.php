<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Services\Task\TaskService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

        DB::beginTransaction();
        try {
            // Salvăm datele task-ului înainte de ștergere
            $taskData = [
                'id' => $task->id,
                'sequence_id' => $task->sequence_id,
                'name' => $task->name
            ];

            if ($activeTimeEntries = $task->timeEntries()->whereNull('end')->get()) {
                $activeTimeEntries->each(function ($timeEntry) {
                    $timeEntry->update(['end' => now(), 'stopped_by_system' => true]);
                });
            }

            // Înregistrăm activitatea cu toate datele necesare
            $task->recordActivity('deleted', [
                'attributes' => $taskData,
                'user_id' => Auth::id()
            ]);

            $this->service->delete($id);
            
            DB::commit();
            return $this->successResponse([], 'Task deleted successfully.', Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
