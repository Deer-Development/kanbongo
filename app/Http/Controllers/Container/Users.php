<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Task\TaskResource;
use App\Models\Container;
use App\Services\Container\ContainerService;
use App\Http\Resources\Container\ContainerResource;
use Illuminate\Http\JsonResponse;

class Users extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = Container::with([
            'members'
        ])->findOrFail($id);

        $tags = $model->members->pluck('user');

        return $this->successResponse($tags, 'Container details fetched successfully.');
    }
}
