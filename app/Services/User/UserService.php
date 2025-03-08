<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserService extends BaseService
{
    protected string $modelClass = User::class;

    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    public function getOptions($request): Collection
    {
        $excludedIds = $request->get('exclude_ids')
            ? explode(',', $request->get('exclude_ids'))
            : [];

        return $this->getQueryBuilder()
            ->when($request->get('q'), function ($query) use ($request) {
                $query->whereAny(
                    ['first_name', 'last_name', 'email'],
                    'ILIKE',
                    '%' . $request->get('q') . '%'
                );
            })
            ->when($excludedIds, function ($query) use ($excludedIds) {
                $query->whereNotIn('id', $excludedIds);
            })
            ->get();
    }

    public function getAll(Request $request)
    {
        $sortBy = $request->input('sortBy', 'id');
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        $query = $this->getModelInstance()
            ->withoutRole('Super-Admin')
            ->applyFilters($request);

        $validSortColumns = $this->getValidSortColumns();

        if (in_array($sortBy, $validSortColumns)) {
            $query->orderBy($sortBy, $orderBy);
        } else {
            $query->orderBy('id', 'asc');
        }

        return $query->paginate($itemsPerPage, ['*'], 'page', $page);
    }

    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        if ($avatar) {
            // Clear existing avatar collection
            $user->clearMediaCollection('avatar');
            
            // Add new avatar with conversions
            $user->addMedia($avatar)
                ->toMediaCollection('avatar');
        }

        $user->update($data);

        return $user->fresh();
    }
}
