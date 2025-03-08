<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Services\ActivityService;
use App\Services\Container\ContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContainerActivities extends BaseController
{
    protected $service;
    protected $activityService;

    public function __construct(ContainerService $service, ActivityService $activityService)
    {
        $this->service = $service;
        $this->activityService = $activityService;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $container = $this->service->getById($id);
        $page = $request->get('page', 1);
        $activities = $this->activityService->getContainerActivities($container, $request->all(), 15);

        $formattedActivities = $activities->map(function ($activity) {
            $description = $this->getActivityDescription($activity);
            
            return [
                'id' => $activity->id,
                'description' => $description,
                'user' => [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->full_name,
                    'avatar' => $activity->causer->avatar,
                    'initials' => $activity->causer->avatar_or_initials,
                ],
                'subject' => [
                    'type' => $activity->subject_type,
                    'id' => $activity->subject_id,
                    'name' => $activity->subject->name ?? null,
                ],
                'event' => $activity->event,
                'properties' => $activity->properties,
                'created_at' => $activity->created_at->diffForHumans(),
                'created_at_formatted' => $activity->created_at->format('Y-m-d H:i:s'),
            ];
        })
        ->filter()
        ->values() // Reindexează array-ul după filtrare
        ->all();   // Convertește colecția în array

        return $this->successResponse([
            'activities' => $formattedActivities,
            'has_more' => $activities->hasMorePages(),
            'next_page' => $activities->currentPage() + 1,
            'total' => $activities->total()
        ], 'Activities fetched successfully.');
    }

    protected function getActivityDescription($activity): string
    {
        $userName = $activity->causer->full_name;
        
        $taskBadge = '';
        if ($activity->subject_type === 'App\\Models\\Task') {
            // Verificăm și în subject dacă nu găsim în properties
            $sequenceId = $activity->properties['attributes']['sequence_id'] 
                ?? $activity->subject->sequence_id 
                ?? null;
            $taskBadge = $sequenceId ? "Task #{$sequenceId}" : '';
        }

        switch ($activity->event) {


            case 'created':
                return "{$userName} created" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'updated':
                if (empty($activity->properties['attributes'])) {
                    return '';
                }
                
                if (isset($activity->properties['attributes']['name'])) {
                    $oldName = $activity->properties['old']['name'] ?? '';
                    $newName = $activity->properties['attributes']['name'];
                    return "{$userName} renamed from \"{$oldName}\" to \"{$newName}\"";
                }
                
                return "{$userName} updated" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'deleted':
                // Nu mai includem Task #sequence_id în descriere, va fi adăugat ca badge în frontend
                $description = "{$userName} deleted";
                Log::info('Deleted Activity Description:', [
                    'description' => $description,
                    'properties' => $activity->properties
                ]);
                return $description;
            
            case 'member_added':
                $memberName = optional(\App\Models\User::find($activity->properties['user_id']))->full_name;
                return "{$userName} added {$memberName} to" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'member_removed':
                $memberName = optional(\App\Models\User::find($activity->properties['user_id']))->full_name;
                return "{$userName} removed {$memberName} from" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'time_entry_completed':
                $duration = abs($activity->properties['duration']);
                $timeFormatted = $this->formatDuration($duration);
                $addedManually = isset($activity->properties['added_manually']) ? ' manually' : '';
                return "{$userName} tracked{$addedManually} {$timeFormatted} on" . ($taskBadge ? " {$taskBadge}" : '');
            
            case 'time_entry_deleted':
                $duration = abs($activity->properties['duration']);
                $timeFormatted = $this->formatDuration($duration);
                $timeEntryUser = optional(\App\Models\User::find($activity->properties['user_id']))->full_name;
                return "{$userName} deleted {$timeEntryUser}'s time entry of {$timeFormatted} from" . 
                       ($taskBadge ? " {$taskBadge}" : '');
            
            case 'time_entry_updated':
                $oldDuration = abs($activity->properties['old_duration']);
                $newDuration = abs($activity->properties['new_duration']);
                $oldTimeFormatted = $this->formatDuration($oldDuration);
                $newTimeFormatted = $this->formatDuration($newDuration);
                $timeEntryUser = optional(\App\Models\User::find($activity->properties['user_id']))->full_name;
                return "{$userName} updated {$timeEntryUser}'s time entry from {$oldTimeFormatted} to {$newTimeFormatted} on" . 
                       ($taskBadge ? " {$taskBadge}" : '');
            
            case 'task_moved':
                // $boards = $activity->properties['boards'];
                // Returnăm doar textul, board-urile vor fi afișate ca badge-uri în frontend
                return "{$userName} moved {$taskBadge} ";
            
            default:
                return "{$userName} performed {$activity->event} on" . ($taskBadge ? " {$taskBadge}" : '');
        }
    }

    // Helper pentru formatarea duratei
    private function formatDuration(int $duration): string
    {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        
        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $seconds
        );
    }
}