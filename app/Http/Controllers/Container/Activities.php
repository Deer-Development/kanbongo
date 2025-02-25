<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Task\TaskResource;
use App\Models\Container;
use App\Services\Container\ContainerService;
use App\Http\Resources\Container\ContainerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Activities extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $filter = $request->input('filters', 'all');
        $search = $request->input('search', '');

        $model = Container::with([
            'boards' => function ($query) use ($filter, $search) {
                $query->orderBy('order')->with([
                    'tasks' => function ($query) use ($filter, $search) {
                        $query->orderBy('order');

                        if ($filter === 'with_comments') {
                            $query->where(function ($q) {
                                $q->whereHas('comments');
                            });
                        }

                        if ($filter === 'without_comments') {
                            $query->where(function ($q) {
                                $q->whereDoesntHave('comments');
                            });
                        }

                        if ($filter === 'with_unread_comments') {
                            $query->where(function ($q) {
                                $q->whereHas('comments', function ($q) {
                                    $q->whereDoesntHave('readByUsers', function ($q) {
                                        $q->where('user_id', auth()->id());
                                    });
                                });
                            });
                        }

                        if ($filter === 'with_mentions') {
                            $query->where(function ($q) {
                                $q->whereHas('comments', function ($q) {
                                    $q->whereHas('mentions', function ($q) {
                                        $q->where('user_id', auth()->id());
                                    });
                                });
                            });
                        }

                        if (!empty($search)) {
                            $query->whereAny(['name', 'id'], 'ILIKE','%' . $search . '%');
                        }
                    }
                ]);
            },
        ])->findOrFail($id);

        return $this->successResponse(new ContainerResource($model), 'Container details fetched successfully.');
    }
}
