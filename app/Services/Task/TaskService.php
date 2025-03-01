<?php

namespace App\Services\Task;

use App\Models\Board;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskService extends BaseService
{
    protected string $modelClass = Task::class;

    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    public function create(array $data): Task
    {
        DB::beginTransaction();

        try {
            $board = Board::with('tasks')->findOrFail($data['boardId']);

            $board->tasks()->increment('order');

            // Get the next sequence_id for this container
            $maxSequence = Task::query()
                ->whereHas('board', function ($query) use ($board) {
                    $query->where('container_id', $board->container_id);
                })
                ->max('sequence_id') ?? 0;

            $task = $board->tasks()->create([
                'name' => $data['name'],
                'order' => 0,
                'sequence_id' => $maxSequence + 1,
                'container_id' => $board->container_id,
            ]);

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function toggleTimer(array $data, int $taskId)
    {
        $task = $this->getById($taskId);
        $userId = $data['user_id'] ?? auth()->id();
        
        // Verificăm dacă există un time entry activ pentru acest utilizator și task
        $timeEntry = $task->timeEntries()
            ->where('user_id', $userId)
            ->whereNull('end')
            ->first();
        
        $action = 'unknown';
        
        if ($timeEntry) {
            // Oprim timer-ul
            $timeEntry->update([
                'end' => now(),
                'stopped_by_system' => $data['stopped_by_system'] ?? false
            ]);
            $action = 'stopped';
        } else {
            // Pornim timer-ul
            $task->timeEntries()->create([
                'user_id' => $userId,
                'start' => now(),
                'container_id' => $task->board->container_id,
                'billable' => $data['billable'],
                'billable_rate' => $data['billable_rate'],
            ]);
            $action = 'started';
        }
        
        // Reîncărcăm task-ul cu relațiile necesare
        $task = $this->getById($taskId);
        
        return [
            'model' => $task,
            'action' => $action
        ];
    }

    public function update(int $id, array $data): Task
    {
        $task = $this->getById($id);

        DB::beginTransaction();

        try {
            $task->update([
                'name' => $data['name'],
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ? Carbon::parse($data['due_date']) : null,
            ]);

            $existingMemberIds = $task->members()->pluck('user_id')->toArray();

            $membersToRemove = array_diff($existingMemberIds, $data['members']);

            $membersToAdd = array_diff($data['members'], $existingMemberIds);

            if (!empty($membersToRemove)) {
                $task->members()->whereIn('user_id', $membersToRemove)->delete();
            }

            $containerMembers = $task->board->container->members()->get();

            foreach ($membersToAdd as $userId) {
                $containerMember = $containerMembers->where('user_id', $userId)->first();
                $task->members()->create([
                    'user_id' => $userId,
                    'can_timing' => $containerMember->can_timing,
                    'billable' => $containerMember->billable,
                    'billable_rate' => $containerMember->billable_rate,
                ]);
            }

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateTimers(array $data, int $taskId): Task
    {
        $task = $this->getById($taskId);

        DB::beginTransaction();
//        dd($data);
        try {
            foreach ($data as $timer) {
                if(isset($timer['deleted']) && $timer['deleted']) {
                    TimeEntry::destroy($timer['id']);
                    continue;
                }
                if (isset($timer['id']) && $timer['id']) {
                    $timeEntry = TimeEntry::find($timer['id']);

                    if (!$timeEntry) {
                        throw new \Exception('Time entry not found.');
                    }

                    $start = isset($timer['start'])
                        ? Carbon::parse($timer['start'])->format('Y-m-d H:i:s')
                        : null;

                    $end = isset($timer['end'])
                        ? Carbon::parse($timer['end'])->format('Y-m-d H:i:s')
                        : null;

                    $timeEntry->update([
                        'start' => $start,
                        'end' => $end,
                    ]);
                } else {
                    if (!empty($timer['start'])) {
                        $start = Carbon::parse($timer['start'])->format('Y-m-d H:i:s');

                        $end = !empty($timer['end'])
                            ? Carbon::parse($timer['end'])->format('Y-m-d H:i:s')
                            : null;

                        if ($start || $end) {
                            TimeEntry::create([
                                'task_id' => $taskId,
                                'member_id' => $timer['member_id'] ?? null,
                                'added_manually' => true,
                                'user_id' => $timer['user_id'],
                                'container_id' => $task->board->container_id,
                                'billable' => $task->board->container->members()->where('user_id', $timer['user_id'])->first()->billable,
                                'billable_rate' => $task->board->container->members()->where('user_id', $timer['user_id'])->first()->billable_rate,
                                'start' => $start,
                                'end' => $end,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function unassignMember(int $taskId, int $userId): Task
    {
        $task = $this->getById($taskId);

        DB::beginTransaction();

        try {
            $task->members()->where('user_id', $userId)->delete();

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

