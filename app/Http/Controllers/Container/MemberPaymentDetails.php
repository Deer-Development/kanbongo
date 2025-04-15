<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Models\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MemberPaymentDetails extends BaseController
{
    public function __invoke(Request $request, int $id, int $userId): JsonResponse
    {
        $dateRange = $request->input('date_range');
        $paymentStatus = $request->input('payment_status', 'all');
        $startDate = null;
        $endDate = null;

        if ($dateRange) {
            [$start, $end] = array_pad(explode(' to ', $dateRange), 2, null);
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = $end ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
        }

        $model = Container::with([
            'timeEntries' => function ($q) use ($userId, $startDate, $endDate, $paymentStatus) {
                $q->where('user_id', $userId);
                if ($startDate) {
                    $q->where('start', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('end', '<=', $endDate);
                }
                if ($paymentStatus !== 'all') {
                    $q->where('is_paid', $paymentStatus === 'paid');
                }
                $q->with('user:id,first_name,last_name,email');
                $q->with(['task' => function ($q) {
                    $q->withTrashed();
                }]);
                $q->withTrashed();
            },
            'members' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }   
        ])->findOrFail($id);

        $billableRate = $model->members->first()->billable_rate;

        // Generate daily time report
        $dailyTimeReport = $this->generateDailyTimeReport($model->timeEntries, $startDate, $endDate);

        $paymentDetails = $model->timeEntries
            ->whereNotNull('end')
            ->groupBy('task_id')
            ->map(function ($entries) use ($userId, $billableRate) {
                $trackedTime = $entries->sum(fn($entry) => $entry->end && !$entry->deleted_at
                    ? Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))
                    : 0);
                
                $task = $entries->first()->task;
                
                // Calculate paid and pending amounts
                $paidAmount = $entries->sum(fn($entry) => 
                    $entry->is_paid && !$entry->deleted_at ? ($entry->amount_paid ?? 0) : 0
                );
                
                $pendingAmount = $entries->sum(fn($entry) => 
                    !$entry->is_paid && !$entry->deleted_at && $entry->end
                        ? (Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end)) / 3600) * $billableRate
                        : 0
                );
                
                return [
                    'task' => $task,
                    'trackedTime' => $trackedTime,
                    'trackedTimeDisplay' => $this->formatTime($trackedTime),
                    'paidAmount' => round($paidAmount, 2),
                    'pendingAmount' => round($pendingAmount, 2),
                    'timeEntries' => $entries
                        ->filter(fn($entry) => !is_null($entry->end))
                        ->map(fn($entry) => [
                            'id' => $entry->id,
                            'start' => $entry->start,
                            'end' => $entry->end,
                            'duration' => $entry->end && !$entry->deleted_at ? $this->formatTime(Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))) : null,
                            'is_paid' => $entry->is_paid,
                            'amount_paid' => $entry->amount_paid,
                            'paid_rate' => $entry->paid_rate,
                            'added_manually' => $entry->added_manually,
                            'deleted_at' => $entry->deleted_at,
                        ])->values(),
                    'entries_logs' => $task ? $task->logs()
                        ->where('loggable_type', 'App\Models\TimeEntry')
                        ->whereHas('loggable', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->orderBy('created_at', 'desc')
                        ->with('user')->get()->map(function ($log) {
                            return [
                                'id' => $log->id,
                                'action' => $log->action,
                                'user' => $log->user,
                                'old_data' => $log->old_data,
                                'new_data' => collect($log->new_data)->map(function ($value, $key) {
                                    return in_array($key, ['start', 'end']) && $value ? Carbon::parse($value)->toIso8601String() : $value;
                                })->toArray(),
                                'created_at' => Carbon::parse($log->created_at)->toIso8601String(),
                            ];
                        }) : [],
                ];
            });

        $response = [
            'paymentDetails' => $paymentDetails,
            'dailyTimeReport' => $dailyTimeReport
        ];

        return $this->successResponse($response, 'Payment details fetched successfully.');
    }

    private function generateDailyTimeReport($timeEntries, $startDate, $endDate)
    {
        // If no date range provided, use the first and last time entry dates
        if (!$startDate || !$endDate) {
            if ($timeEntries->isEmpty()) {
                return [];
            }
            
            $allDates = $timeEntries->map(fn($entry) => Carbon::parse($entry->start)->startOfDay());
            $startDate = $allDates->min();
            $endDate = $allDates->max();
        }

        // Create a date range
        $period = new \DatePeriod(
            Carbon::parse($startDate),
            new \DateInterval('P1D'),
            Carbon::parse($endDate)->addDay() // Add one day to include the end date
        );

        // Initialize daily report with zeros
        $dailyReport = [];
        foreach ($period as $date) {
            $dailyReport[$date->format('Y-m-d')] = [
                'date' => $date->format('Y-m-d'),
                'displayDate' => $date->format('M d, Y'),
                'totalSeconds' => 0,
                'hoursByTask' => [],
                'tasks' => []
            ];
        }

        // Process time entries
        foreach ($timeEntries as $entry) {
            if (!$entry->end || $entry->deleted_at) {
                continue;
            }

            $startTime = Carbon::parse($entry->start);
            $endTime = Carbon::parse($entry->end);
            
            // Handle entries that span multiple days
            $currentDay = $startTime->copy()->startOfDay();
            $lastDay = $endTime->copy()->startOfDay();
            
            while ($currentDay->lte($lastDay)) {
                $dayKey = $currentDay->format('Y-m-d');
                
                // Skip if the day is outside our report range
                if (!isset($dailyReport[$dayKey])) {
                    $currentDay->addDay();
                    continue;
                }
                
                // Calculate start and end times for this day
                $dayStart = max($startTime, $currentDay);
                $dayEnd = min($endTime, $currentDay->copy()->endOfDay());
                
                // Calculate seconds worked on this day
                $secondsWorked = $dayStart->diffInSeconds($dayEnd);
                
                if ($secondsWorked > 0) {
                    $dailyReport[$dayKey]['totalSeconds'] += $secondsWorked;
                    
                    // Track task-specific hours
                    $taskId = $entry->task_id;
                    $taskName = $entry->task->name ?? "Task #$taskId";
                    
                    if (!isset($dailyReport[$dayKey]['hoursByTask'][$taskId])) {
                        $dailyReport[$dayKey]['hoursByTask'][$taskId] = 0;
                        $dailyReport[$dayKey]['tasks'][$taskId] = [
                            'id' => $taskId,
                            'name' => $taskName,
                            'seconds' => 0,
                            'color' => $this->generateTaskColor($taskId)
                        ];
                    }
                    
                    $dailyReport[$dayKey]['hoursByTask'][$taskId] += $secondsWorked;
                    $dailyReport[$dayKey]['tasks'][$taskId]['seconds'] += $secondsWorked;
                }
                
                $currentDay->addDay();
            }
        }
        
        // Post-process to add display values and sort tasks
        foreach ($dailyReport as &$day) {
            $day['displayHours'] = $this->formatTime($day['totalSeconds']);
            $day['hoursDecimal'] = round($day['totalSeconds'] / 3600, 2);
            
            // Convert tasks to array and sort by time
            $day['tasks'] = array_values($day['tasks']);
            usort($day['tasks'], function($a, $b) {
                return $b['seconds'] - $a['seconds'];
            });
            
            // Add display times to tasks
            foreach ($day['tasks'] as &$task) {
                $task['displayTime'] = $this->formatTime($task['seconds']);
                $task['percentage'] = $day['totalSeconds'] > 0 
                    ? round(($task['seconds'] / $day['totalSeconds']) * 100) 
                    : 0;
            }
            
            // Remove hoursByTask as it's no longer needed
            unset($day['hoursByTask']);
        }
        
        // Return as array values
        return array_values($dailyReport);
    }

    private function generateTaskColor($taskId)
    {
        // Generate consistent colors based on task ID
        $colors = [
            '#4f46e5', // Indigo
            '#0ea5e9', // Sky
            '#10b981', // Emerald
            '#f59e0b', // Amber
            '#ef4444', // Red
            '#8b5cf6', // Violet
            '#ec4899', // Pink
            '#f97316', // Orange
            '#14b8a6', // Teal
            '#6366f1', // Blue
        ];
        
        return $colors[$taskId % count($colors)];
    }

    private function formatTime($seconds = 0)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
