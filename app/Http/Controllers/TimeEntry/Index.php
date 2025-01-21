<?php

namespace App\Http\Controllers\TimeEntry;

use App\Http\Controllers\BaseController;
use App\Services\TimeEntry\TimeEntryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\TimeEntry\TimeEntryResource;

class Index extends BaseController
{
    protected TimeEntryService $service;

    public function __construct(TimeEntryService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => TimeEntryResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'TimeEntry list retrieved successfully.');
    }
}
