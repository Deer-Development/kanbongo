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

            $task = $board->tasks()->create([
                'name' => $data['name'],
                'order' => 0,
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
        DB::beginTransaction();

        try {
            $task = $this->getById($taskId)->load('timeEntries');

            if (!$data['isTiming']) {
                $timeEntry = $task->timeEntries()
                    ->where('user_id', $data['user_id'])
                    ->whereNull('end')
                    ->first();

                if (!$timeEntry) {
                    throw new \Exception('No running timer found.');
                }

                $timeEntry->update(['end' => now()]);
            } else {
                $task->timeEntries()->create([
                    'start' => now(),
                    'user_id' => $data['user_id'],
                    'container_id' => $task->board->container_id,
                    'billable' => $data['billable'],
                    'billable_rate' => $data['billable_rate'],
                ]);
            }

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): Task
    {
        $task = $this->getById($id);

        DB::beginTransaction();

        try {
            $task->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'priority' => $data['priority'],
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

        try {
            foreach ($data as $timer) {
                if (isset($timer['id']) && $timer['id']) {
                    $timeEntry = TimeEntry::find($timer['id']);

                    if (!$timeEntry) {
                        throw new \Exception('Time entry not found.');
                    }

                    $start = isset($timer['start'])
                        ? Carbon::createFromFormat('m/d/Y h:i:s A', $timer['start'])->format('Y-m-d H:i:s')
                        : null;

                    $end = isset($timer['end'])
                        ? Carbon::createFromFormat('m/d/Y h:i:s A', $timer['end'])->format('Y-m-d H:i:s')
                        : null;

                    $timeEntry->update([
                        'start' => $start,
                        'end' => $end,
                    ]);
                }
                else {
                    if (!empty($timer['start'])) {
                        $start = Carbon::createFromFormat('m/d/Y h:i:s A', $timer['start'])->format('Y-m-d H:i:s');

                        $end = !empty($timer['end'])
                            ? Carbon::createFromFormat('m/d/Y h:i:s A', $timer['end'])->format('Y-m-d H:i:s')
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
}

