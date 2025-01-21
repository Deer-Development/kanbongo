<?php

namespace App\Services\Board;

use App\Http\Resources\Board\BoardResource;
use App\Models\Board;
use App\Models\Task;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BoardService extends BaseService
{
    protected string $modelClass = Board::class;

    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    public function create(array $data): Model
    {
        $order = Board::where('container_id', $data['container_id'])->max('order') + 1;

        return DB::transaction(function () use ($data, $order) {
            return $this->getModelInstance()->create([
                'container_id' => $data['container_id'],
                'name' => $data['name'],
                'color' => $data['color'],
                'order' => $order,
            ]);
        }, 3);
    }

    public function updateState(int $id, array $data)
    {
        $board = $this->getById($id);

        $updateData = [];
        foreach ($data as $order => $taskId) {
            $updateData[] = [
                'id' => $taskId,
                'board_id' => $id,
                'order' => $order,
            ];
        }

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $task) {
                Task::where('id', $task['id'])
                    ->update([
                        'board_id' => $task['board_id'],
                        'order' => $task['order'],
                    ]);
            }
        });

        return new BoardResource($board);
    }
}
