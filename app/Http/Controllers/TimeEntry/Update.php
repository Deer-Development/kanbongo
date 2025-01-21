<?php

namespace App\Http\Controllers\TimeEntry;

use App\Http\Controllers\BaseController;
use App\Http\Requests\TimeEntry\ValidateTimeEntryUpdate;
use App\Services\TimeEntry\TimeEntryService;
use App\Http\Resources\TimeEntry\TimeEntryResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(TimeEntryService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTimeEntryUpdate $request, int $id): JsonResponse
    {
        $model = $this->service->update($id, $request->validated());

        return $this->successResponse(new TimeEntryResource($model), 'TimeEntry updated successfully.', Response::HTTP_OK);
    }
}
