<?php

namespace App\Http\Controllers\TimeEntry;

use App\Http\Controllers\BaseController;
use App\Http\Requests\TimeEntry\ValidateTimeEntryStore;
use App\Services\TimeEntry\TimeEntryService;
use App\Http\Resources\TimeEntry\TimeEntryResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(TimeEntryService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTimeEntryStore $request): JsonResponse
    {
        $model = $this->service->create($request->validated());

        return $this->successResponse(new TimeEntryResource($model), 'TimeEntry created successfully.', Response::HTTP_CREATED);
    }
}
