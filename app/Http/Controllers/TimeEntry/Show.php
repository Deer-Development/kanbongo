<?php

namespace App\Http\Controllers\TimeEntry;

use App\Http\Controllers\BaseController;
use App\Services\TimeEntry\TimeEntryService;
use App\Http\Resources\TimeEntry\TimeEntryResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(TimeEntryService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new TimeEntryResource($model), 'TimeEntry details fetched successfully.');
    }
}
