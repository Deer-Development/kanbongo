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

        $board = Board::with('tasks')->find($data['boardId']);

        try {
            $maxOrder = $board->tasks()->max('order') ?? 0;

            $task = $board->tasks()->create([
                'name' => $data['name'],
                'order' => $maxOrder + 1,
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
                $timeEntry = TimeEntry::find($timer['id']);

                if (!$timeEntry) {
                    throw new \Exception('Time entry not found.');
                }

                $start = Carbon::createFromFormat('m/d/Y h:i:s A', $timer['start'])->format('Y-m-d H:i:s');
                $end = Carbon::createFromFormat('m/d/Y h:i:s A', $timer['end'])->format('Y-m-d H:i:s');

                if ($start !== $timeEntry->start->format('Y-m-d H:i:s')) {
                    $timeEntry->logs()->create([
                        'entry' => $start,
                        'old_entry' => $timeEntry->start->format('Y-m-d H:i:s'),
                        'field' => 'start',
                        'user_id' => auth()->id(),
                    ]);
                }

                if ($end !== $timeEntry->end->format('Y-m-d H:i:s')) {
                    $timeEntry->logs()->create([
                        'entry' => $end,
                        'old_entry' => $timeEntry->end->format('Y-m-d H:i:s'),
                        'field' => 'end',
                        'user_id' => auth()->id(),
                    ]);
                }

                $timeEntry->update([
                    'start' => $start,
                    'end' => $end,
                ]);
            }

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

