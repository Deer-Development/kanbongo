<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\BaseController;
use App\Http\Resources\User\UserResource;
use App\Models\Project;
use App\Services\Project\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class Show extends BaseController
{
    protected $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $user = Auth::user();

        $status = request()->get('status');

        $model = Project::with(['owner', 'containers' => function ($q) use ($user, $status) {
            if ($user->hasRole('Super-Admin')) {
                $q->with(['members' => function ($q) {
                    $q->with('user');
                }]);
            } else {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->with(['members' => function ($q) {
                        $q->with('user');
                    }]);
            }
            if ($status && $status !== 'all') {
                $q->where('is_active', $status === 'active');
            }
            $q->with('owner');
            $q->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        $modelArray = $model->toArray();
        
        foreach ($modelArray['containers'] as &$container) {
            foreach ($container['members'] as &$member) {
                $member['user'] = (new UserResource($model->containers
                    ->find($container['id'])
                    ->members
                    ->find($member['id'])
                    ->user))
                    ->toArray(request());
            }
        }

        $response = [
            'project' => $modelArray,
            'isSuperAdmin' => $user->hasRole('Super-Admin'),
        ];

        return $this->successResponse($response, 'Project details fetched successfully.');
    }
}
