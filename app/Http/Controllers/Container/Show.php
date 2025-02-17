<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Task\TaskResource;
use App\Models\Container;
use App\Services\Container\ContainerService;
use App\Http\Resources\Container\ContainerResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = Container::with([
            'members.user:id,first_name,last_name,email',
            'owner:id,first_name,last_name,email',
            'timeEntries' => function ($q) {
                $q->with('user:id,first_name,last_name,email');
            },
            'boards' => function ($q) use ($id) {
                $q->orderBy('order')
                    ->with([
                    'tasks' => function ($q) use ($id) {
                        $q->orderBy('order');
                        $q->with(['members.user:id,first_name,last_name,email', 'tags']);
                    },
                ]);
            },
        ])->findOrFail($id);

        return $this->successResponse(new ContainerResource($model), 'Container details fetched successfully.');
    }
}
