<?php

namespace App\Http\Controllers\Total;

use App\Http\Controllers\BaseController;
use App\Services\Total\TotalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Total\TotalResource;

class Index extends BaseController
{
    protected TotalService $service;

    public function __construct(TotalService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => TotalResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'Total list retrieved successfully.');
    }
}
