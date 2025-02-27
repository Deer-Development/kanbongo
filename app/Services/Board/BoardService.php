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

    public function destroy(int $id, ?int $targetBoardId = null): void
    {
        DB::transaction(function () use ($id, $targetBoardId) {
            $board = $this->getById($id);
            $currentOrder = $board->order;
            
            // If we have tasks to move and a target board
            if ($targetBoardId !== null && $board->tasks()->count() > 0) {
                $targetBoard = $this->getById($targetBoardId);
                
                // Get max task order from target board
                $maxTaskOrder = $targetBoard->tasks()
                    ->orderBy('order', 'desc')
                    ->value('order') ?? -1;
                
                // Move and reorder tasks to target board
                $tasksToMove = $board->tasks()
                    ->orderBy('order')
                    ->get();
                    
                foreach ($tasksToMove as $index => $task) {
                    $task->update([
                        'board_id' => $targetBoardId,
                        'order' => $maxTaskOrder + $index + 1
                    ]);
                }
            }
            
            // Delete the board
            $board->delete();
            
            // Resequence remaining boards in the container
            $boardsToReorder = Board::where('container_id', $board->container_id)
                ->where('order', '>', $currentOrder)
                ->orderBy('order')
                ->get();
                
            foreach ($boardsToReorder as $boardToReorder) {
                $boardToReorder->update([
                    'order' => $boardToReorder->order - 1
                ]);
            }
        }, 3); // 3 retries on deadlock
    }
}
