<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Task\ValidateTaskUpdate;
use App\Services\Task\TaskService;
use App\Http\Resources\Task\TaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ToggleTimer extends BaseController
{
    protected $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $result = $this->service->toggleTimer($request->all(), $id);
        
        // Extragem modelul și informațiile despre acțiune
        $model = $result['model'] ?? $result;
        $action = $result['action'] ?? null;
        
        // Construim răspunsul cu informații suplimentare
        $responseData = [
            'task' => new TaskResource($model),
            'action' => $action ?? 'unknown', // 'started' sau 'stopped'
            'task_sequence_id' => $model->sequence_id ?? $model->id
        ];
        
        return $this->successResponse($responseData, 'Timer updated successfully.', Response::HTTP_OK);
    }
}
