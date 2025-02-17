<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TaskBoardResource extends JsonResource
{
    public function toArray($request)
    {
        $user = auth()->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'board_id' => $this->board_id,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'comments_count' => $this->comments->count(),
            'has_unread_comments' => $this->comments()
                ->whereDoesntHave('readByUsers', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->exists(),
            'members' => $this->members,
            'tags' => $this->tags,
            'tracked_time' => $this->tracked_time,
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
