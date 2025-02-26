<?php

namespace App\Http\Resources\Task;

use App\Http\Resources\Comment\CommentResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sequence_id' => $this->sequence_id,
            'name' => $this->name,
            'board_id' => $this->board_id,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'comments' => CommentResource::collection($this->comments()->with('replies')->orderBy('created_at', 'ASC')->get()),
            'members' => $this->members->map(function ($member) {
                $timeEntries = $member->user->timeEntries()
                    ->where('task_id', $this->id)
                    ->get();

                $totalTrackedTime = $timeEntries->sum(function ($timeEntry) {
                    return $this->calculateTrackedTime($timeEntry);
                });

                return array_merge($member->toArray(), [
                    'timeEntries' => $timeEntries->map(function ($timeEntry) {
                        $trackedTime = $this->calculateTrackedTime($timeEntry);
                        return [
                            'id' => $timeEntry->id,
                            'start' => Carbon::parse($timeEntry->start)->format('m/d/Y h:i:s A'),
                            'end' => $timeEntry->end ? Carbon::parse($timeEntry->end)->format('m/d/Y h:i:s A') : null,
                            'trackedTime' => $trackedTime,
                            'trackedTimeDisplay' => $this->formatTime($trackedTime),
                            'added_manually' => $timeEntry->added_manually,
                            'logs' => $timeEntry->logs()->orderBy('created_at', 'ASC')->get()->map(function ($log) {
                                return [
                                    'id' => $log->id,
                                    'entry' => $log->entry,
                                    'old_entry' => $log->old_entry,
                                    'field' => $log->field,
                                    'created_by' => $log->user?->full_name,
                                    'created_at' => $log->created_at->diffForHumans(),
                                ];
                            }),
                        ];
                    }),
                    'isTiming' => $this->isMemberTiming($member),
                    'totalTrackedTime' => $totalTrackedTime,
                    'totalTrackedTimeDisplay' => $this->formatTime($totalTrackedTime),
                ]);
            }),
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Calculate tracked time for a time entry.
     *
     * @param  object $timeEntry
     * @return int|null
     */
    private function calculateTrackedTime($timeEntry)
    {
        if (!$timeEntry) {
            return null;
        }

        $start = Carbon::parse($timeEntry->start);
        $end = $timeEntry->end ? Carbon::parse($timeEntry->end) : now();

        return $start->lte($end) ? $start->diffInSeconds($end) : 0;
    }

    /**
     * Format time from seconds to HH:MM:SS format.
     *
     * @param  int $seconds
     * @return string
     */
    private function formatTime($seconds = 0)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Check if a member has an active timer.
     *
     * @param  object $member
     * @return bool
     */
    private function isMemberTiming($member)
    {
        $latestEntry = $member->user->timeEntries()
            ->where('task_id', $this->id)
            ->latest('start')
            ->first();

        return $latestEntry && !$latestEntry->end;
    }
}
