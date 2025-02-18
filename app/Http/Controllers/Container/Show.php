<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Task\TaskResource;
use App\Models\Container;
use App\Models\ContainerFilter;
use App\Services\Container\ContainerService;
use App\Http\Resources\Container\ContainerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Show extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $savedFilter = ContainerFilter::where('user_id', $user->id)
            ->where('container_id', $id)
            ->first();

        $filters = $savedFilter ? $savedFilter->filters : [];

        if ($request->has('filters')) {
            $newFilters = $request->input('filters');

            if (!empty($savedFilter) && $newFilters === ['priority' => 0, 'users' => [], 'tags' => []]) {
                $filters = $savedFilter->filters;
            } else {
                $filters = array_merge($filters, $newFilters);

                if ($request->boolean('save') && $filters !== ['priority' => 0, 'users' => [], 'tags' => []]) {
                    ContainerFilter::updateOrCreate(
                        ['user_id' => $user->id, 'container_id' => $id],
                        ['filters' => $filters]
                    );
                }
            }
        }

        $query = Container::with([
            'members.user:id,first_name,last_name,email',
            'owner:id,first_name,last_name,email',
            'timeEntries' => function ($q) {
                $q->with('user:id,first_name,last_name,email');
            },
            'boards' => function ($q) use ($id, $filters) {
                $q->orderBy('order')
                    ->with([
                        'tasks' => function ($q) use ($filters) {
                            $q->orderBy('order')
                                ->with(['members.user:id,first_name,last_name,email', 'tags']);

                            if (!empty($filters['priority'])) {
                                $q->where('priority', $filters['priority']);
                            }
                            if (!empty($filters['users'])) {
                                $hasUnassigned = in_array("unassigned", $filters['users']);
                                $filteredUsers = array_filter($filters['users'], fn($user) => $user !== "unassigned");

                                $q->where(function ($q) use ($filteredUsers, $hasUnassigned) {
                                    if (!empty($filteredUsers)) {
                                        $q->whereHas('members', function ($q) use ($filteredUsers) {
                                            $q->whereIn('user_id', $filteredUsers);
                                        });
                                    }
                                    if ($hasUnassigned) {
                                        $q->orWhereDoesntHave('members');
                                    }
                                });
                            }
                            if (!empty($filters['tags'])) {
                                $q->whereHas('tags', function ($q) use ($filters) {
                                    $q->whereIn('tag_id', $filters['tags']);
                                });
                            }
                        },
                    ]);
            },
        ])->findOrFail($id);

        return $this->successResponse([
            'container' => new ContainerResource($query),
            'filters' => $filters,
        ], 'Container details fetched successfully.');
    }
}
