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
            'members.user',
            'owner',
            'boards' => function ($q) {
                $q->orderBy('created_at')->with([
                    'members',
                    'tasks' => function ($q) {
                        $q->with(['members.user']);
                        $q->with(['timeEntries' => function ($q) {
                            $q->with('logs');
                        }]);
                    },
                ]);
            },
        ])->findOrFail($id);

        return $this->successResponse(new ContainerResource($model), 'Container details fetched successfully.');
    }
}
