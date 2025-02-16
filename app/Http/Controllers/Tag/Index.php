<?php

namespace App\Http\Controllers\Tag;

use App\Http\Controllers\BaseController;
use App\Services\Tag\TagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Tag\TagResource;

class Index extends BaseController
{
    protected TagService $service;

    public function __construct(TagService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => TagResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'Tag list retrieved successfully.');
    }
}
