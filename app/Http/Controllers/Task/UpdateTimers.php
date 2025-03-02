<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Task\ValidateTaskUpdate;
use App\Services\Task\TaskService;
use App\Http\Resources\Task\TaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UpdateTimers extends BaseController
{
    protected $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $model = $this->service->updateTimers($request->all(), $id);

        return $this->successResponse(new TaskResource($model), 'Timers updated successfully.', Response::HTTP_OK);
    }
}
