<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Task\TaskResource;
use App\Models\Container;
use App\Services\Container\ContainerService;
use App\Http\Resources\Container\ContainerResource;
use Illuminate\Http\JsonResponse;

class Tags extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = Container::with([
            'tags'
        ])->findOrFail($id);

        $tags = $model->tags;

        return $this->successResponse($tags, 'Container details fetched successfully.');
    }
}
