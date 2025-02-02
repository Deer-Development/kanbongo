<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\BaseController;
use App\Services\Task\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class TimeEntries extends BaseController
{
    protected $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $task = $this->service->getModelInstance()
            ->with([
                'timeEntries' => function ($query) {
                    $query->orderBy('start', 'desc')->with('user');
                },
                'members.user'
            ])
            ->findOrFail($id);

        $users = collect($task->timeEntries)
            ->pluck('user')
            ->merge(collect($task->members)->pluck('user'))
            ->unique('id');

        $entriesByUser = $task->timeEntries->groupBy('user_id');

        $groupedEntries = $users->map(function ($user) use ($entriesByUser) {
            $entries = $entriesByUser->get($user->id, collect());

            $hasActiveTimer = $entries->contains(fn($entry) => is_null($entry->end));
            $totalWorkedSeconds = $entries->sum(fn($entry) => $entry->end
                ? Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))
                : 0);
            $totalWorkedTime = $this->formatTime($totalWorkedSeconds);

            return [
                'details' => [
                    'hasActiveTimer' => $hasActiveTimer,
                    'totalWorkedTime' => $totalWorkedTime ?: '00:00:00',
                    'user' => [
                        'id' => $user->id,
                        'full_name' => "{$user->first_name} {$user->last_name}",
                        'avatar' => $user->avatar ?? null,
                        'avatar_or_initials' => $user->avatar ? $user->avatar : strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)),
                    ],
                ],
                'time_entries' => $entries
                    ->filter(fn($entry) => !is_null($entry->end))
                    ->map(fn($entry) => [
                    'id' => $entry->id,
                    'start' => $entry->start,
                    'end' => $entry->end,
                    'duration' => gmdate("H:i:s", Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))),
                    'billable_rate' => $entry->billable_rate,
                    'billable' => $entry->billable,
                    'is_paid' => $entry->is_paid,
                    'added_manually' => $entry->added_manually,
                ])->values(),
            ];
        });

        return $this->successResponse($groupedEntries, 'Time entries grouped successfully.');
    }

    private function formatTime($seconds = 0)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}


