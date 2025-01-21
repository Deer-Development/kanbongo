<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectService extends BaseService
{
    protected string $modelClass = Project::class;

    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    public function getAll(Request $request)
    {
        $sortBy = $request->input('sortBy', 'id');
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        $user = Auth::user();

        $query = $this->getModelInstance()->with(['owner', 'containers' => function ($q) use ($user) {
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
        }]);

        $query = $query->applyFilters($request);

        $validSortColumns = $this->getValidSortColumns();
        if (in_array($sortBy, $validSortColumns)) {
            $query->orderBy($sortBy, $orderBy);
        } else {
            $query->orderBy('id', 'asc');
        }

        return $query->paginate($itemsPerPage, ['*'], 'page', $page);
    }
}
