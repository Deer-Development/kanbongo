<?php

namespace App\Services\Container;

use App\Http\Resources\Container\ContainerResource;
use App\Models\Container;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class ContainerService extends BaseService
{
    protected string $modelClass = Container::class;

    // Define valid columns for sorting
    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    public function create(array $data): Container
    {
        return DB::transaction(function () use ($data) {
            $containerData = $this->prepareContainerData($data);
            $members = $data['members'] ?? [];

            $container = $this->getModelInstance()->create($containerData);

            foreach ($members as $memberData) {
                $container->members()->create($memberData);
            }

            return $container;
        }, 3);
    }

    public function update($id, $data): Container
    {
        return DB::transaction(function () use ($id, $data) {
            $containerData = $this->prepareContainerData($data);
            $members = $data['members'] ?? [];

            $container = $this->getModelInstance()->findOrFail($id);
            $container->update($containerData);

            $container->members()->get()->map(function ($member) use ($members) {
                if (!in_array($member->user_id, array_column($members, 'user_id'))) {
                    $member->delete();
                }
            });

            foreach ($members as $memberData) {
                $container->members()->updateOrCreate(
                    [
                        'user_id' => $memberData['user_id'],
                    ],
                    $memberData
                );
            }

            return $container;
        }, 3);
    }

    public function updateState(int $id, array $data): ContainerResource
    {
        $container = $this->getById($id)->load('boards');

        foreach ($data as $index => $boardId) {
            $container
                ->boards()->where('id', $boardId)
                ->update(['order' => $index + 1]);
        }

        return new ContainerResource($container);
    }


    private function prepareContainerData(array $data): array
    {
        return [
            'name' => $data['name'],
            'project_id' => $data['project_id'],
            'is_active' => $data['is_active'],
            'owner_id' => $data['owner_id'] ?? auth()->id(),
        ];
    }
}
