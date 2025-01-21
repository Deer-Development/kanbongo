<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Services\Container\ContainerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Container\ContainerResource;

class Index extends BaseController
{
    protected ContainerService $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => ContainerResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'Container list retrieved successfully.');
    }
}
