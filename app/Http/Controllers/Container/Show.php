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

            if (!empty($savedFilter) && !$request->boolean('save')) {
                $filters = $savedFilter->filters;
            } else {
                $filters = array_merge($filters, $newFilters);

                if ($request->boolean('save')) {
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
                                $hasUnflagged = in_array("unflagged", $filters['priority']);
                                $filteredPriorities = array_filter($filters['priority'], fn($priority) => $priority !== "unflagged");

                                $q->where(function ($q) use ($filteredPriorities, $hasUnflagged) {
                                    if (!empty($filteredPriorities)) {
                                        $q->whereIn('priority', $filteredPriorities);
                                    }
                                    if ($hasUnflagged) {
                                        $q->orWhere('priority', 0);
                                    }
                                });
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
                                $hasUntagged = in_array("untagged", $filters['tags']);
                                $filteredTags = array_filter($filters['tags'], fn($tag) => $tag !== "untagged");

                                $q->where(function ($q) use ($filteredTags, $hasUntagged) {
                                    if (!empty($filteredTags)) {
                                        $q->whereHas('tags', function ($q) use ($filteredTags) {
                                            $q->whereIn('tag_id', $filteredTags);
                                        });
                                    }
                                    if ($hasUntagged) {
                                        $q->orWhereDoesntHave('tags');
                                    }
                                });
                            }
                            if (!empty($filters['search'])) {
                                $q->whereAny(['name', 'id'], 'ILIKE','%' . $filters['search'] . '%');
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
