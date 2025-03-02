<?php

namespace App\Services\Task;

use App\Models\Board;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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

            $maxSequence = Task::query()
                ->withTrashed()
                ->where('container_id', $board->container_id)
                ->max('sequence_id') ?? 0;

            $task = $board->tasks()->create([
                'name' => $data['name'],
                'order' => 0,
                'sequence_id' => $maxSequence + 1,
                'container_id' => $board->container_id,
            ]);

            // Activity va fi înregistrată automat prin evenimentul created

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
        $userId = $data['user_id'] ?? Auth::id();
        
        $timeEntry = $task->timeEntries()
            ->where('user_id', $userId)
            ->whereNull('end')
            ->first();
        
        $action = 'unknown';

        if ($timeEntry) {
            $timeEntry->update([
                'end' => now(),
                'stopped_by_system' => $data['stopped_by_system'] ?? false
            ]);

            // Înregistrăm activitatea doar când se completează time entry-ul
            $duration = $timeEntry->end->diffInSeconds($timeEntry->start);
            $task->recordActivity('time_entry_completed', [
                'attributes' => [
                    'sequence_id' => $task->sequence_id,
                ],
                'time_entry_id' => $timeEntry->id,
                'duration' => $duration,
                'user_id' => $userId
            ]);

            $action = 'stopped';
        } else {
            // Pornim timer-ul fără a înregistra activitate
            $task->timeEntries()->create([
                'user_id' => $userId,
                'start' => now(),
                'container_id' => $task->board->container_id,
                'billable' => $data['billable'],
                'billable_rate' => $data['billable_rate'],
            ]);

            $action = 'started';
        }
        
        return [
            'model' => $this->getById($taskId),
            'action' => $action
        ];
    }

    public function update(int $id, array $data): Task
    {
        $task = $this->getById($id);
        
        DB::beginTransaction();
        
        try {
            $batchUuid = Task::withBatch();
            
            $task->update([
                'name' => $data['name'],
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ? Carbon::parse($data['due_date']) : null,
            ]);

            $existingMemberIds = $task->members()->pluck('user_id')->toArray();
            $membersToRemove = array_diff($existingMemberIds, $data['members']);
            $membersToAdd = array_diff($data['members'], $existingMemberIds);

            foreach ($membersToRemove as $userId) {
                $task->members()->where('user_id', $userId)->delete();
                $task->recordActivity('member_removed', [
                    'attributes' => [
                        'sequence_id' => $task->sequence_id,
                    ],
                    'user_id' => $userId
                ]);
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
                $task->recordActivity('member_added', [
                    'attributes' => [
                        'sequence_id' => $task->sequence_id,
                    ],
                    'user_id' => $userId
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
                if(isset($timer['deleted']) && $timer['deleted']) {
                    $timeEntry = TimeEntry::find($timer['id']);
                    if ($timeEntry) {
                        // Înregistrăm activitate pentru ștergere manuală
                        $task->recordActivity('time_entry_deleted', [
                            'attributes' => [
                                'sequence_id' => $task->sequence_id,
                            ],
                            'time_entry_id' => $timeEntry->id,
                            'duration' => $timeEntry->end?->diffInSeconds($timeEntry->start),
                            'user_id' => $timeEntry->user_id
                        ]);
                        TimeEntry::destroy($timer['id']);
                    }
                    continue;
                }

                if (isset($timer['id']) && $timer['id']) {
                    $timeEntry = TimeEntry::find($timer['id']);

                    if (!$timeEntry) {
                        throw new \Exception('Time entry not found.');
                    }

                    $oldStart = $timeEntry->start;
                    $oldEnd = $timeEntry->end;

                    $start = isset($timer['start'])
                        ? Carbon::parse($timer['start'])->format('Y-m-d H:i:s')
                        : null;

                    $end = isset($timer['end'])
                        ? Carbon::parse($timer['end'])->format('Y-m-d H:i:s')
                        : null;

                    $timeEntry->update([
                        'start' => $start,
                        'end' => $end,
                        'added_manually' => true
                    ]);

                    // Înregistrăm activitate pentru modificări manuale
                    if ($oldStart != $timeEntry->start || $oldEnd != $timeEntry->end) {
                        $task->recordActivity('time_entry_updated', [
                            'attributes' => [
                                'sequence_id' => $task->sequence_id,
                            ],
                            'time_entry_id' => $timeEntry->id,
                            'old_duration' => $oldEnd?->diffInSeconds($oldStart),
                            'new_duration' => $timeEntry->end?->diffInSeconds($timeEntry->start),
                            'user_id' => $timeEntry->user_id
                        ]);
                    }
                } else {
                    if (!empty($timer['start'])) {
                        $start = Carbon::parse($timer['start'])->format('Y-m-d H:i:s');
                        $end = isset($timer['end'])
                            ? Carbon::parse($timer['end'])->format('Y-m-d H:i:s')
                            : null;

                        $timeEntry = $task->timeEntries()->create([
                            'member_id' => $timer['member_id'] ?? null,
                            'added_manually' => true,
                            'user_id' => $timer['user_id'],
                            'container_id' => $task->board->container_id,
                            'billable' => $task->board->container->members()->where('user_id', $timer['user_id'])->first()->billable,
                            'billable_rate' => $task->board->container->members()->where('user_id', $timer['user_id'])->first()->billable_rate,
                            'start' => $start,
                            'end' => $end,
                        ]);

                        // Înregistrăm activitate pentru time entry creat manual cu end time
                        if ($timeEntry->end) {
                            $task->recordActivity('time_entry_completed', [
                                           'attributes' => [
                                    'sequence_id' => $task->sequence_id,
                                ],
                                'time_entry_id' => $timeEntry->id,
                                'duration' => $timeEntry->end->diffInSeconds($timeEntry->start),
                                'user_id' => $timer['user_id'],
                                'added_manually' => true
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return $task->load('timeEntries');
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

