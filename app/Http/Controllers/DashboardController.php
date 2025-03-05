<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Paycheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');
        $startDate = null;
        $endDate = null;

        // Set date range based on period
        switch ($period) {
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $dateRange = $request->input('date_range');
                if ($dateRange) {
                    [$start, $end] = array_pad(explode(' to ', $dateRange), 2, null);
                    $startDate = Carbon::parse($start)->startOfDay();
                    $endDate = $end ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
                }
                break;
        }

        // Get user's containers (as member)
        $memberContainers = Container::whereHas('members')->with(['members', 'project'])->get();

        // Get containers owned by user
        $ownedContainers = Container::where('owner_id', $user->id)
            ->with(['members.user', 'project'])
            ->get();

        // Calculate income data (for containers where user is a member)
        $incomeData = $this->calculateIncomeData($memberContainers, $user->id, $startDate, $endDate);

        // Calculate spending data (for containers where user is an owner)
        $spendingData = $this->calculateSpendingData($ownedContainers, $user->id, $startDate, $endDate);

        // Get active projects
        $activeProjects = $this->getActiveProjects($user->id);

        // Get recent activity
        $recentActivity = $this->getRecentActivity($user->id);

        return $this->successResponse([
            'stats' => [
                'total_hours' => $incomeData['total_hours'],
                'previous_hours' => 0,
                'hours_trend' => 0,
                'total_income' => $incomeData['total_income'],
                'previous_income' => 0,
                'income_trend' => 0,
                'active_projects' => count($activeProjects),
                'total_projects' => 0,
                'projects_trend' => 0,
            ],
            'income' => [
                'containers' => $incomeData['containers'],
                'total_income' => $incomeData['total_income'],
                'total_hours' => $incomeData['total_hours'],
                'pending_income' => $incomeData['pending_income'],
                'pending_hours' => $incomeData['pending_hours'],
                'grand_total' => $incomeData['grand_total']
            ],
            'spending' => [
                'containers' => $spendingData['containers'],
                'total_spending' => $spendingData['total_spending'],
                'pending_spending' => $spendingData['pending_spending'],
                'grand_total' => $spendingData['grand_total']
            ],
            'active_projects' => $activeProjects,
            'recent_activity' => $recentActivity
        ]);
    }

    private function calculateIncomeData($containers, $userId, $startDate, $endDate)
    {
        $containerIncomes = [];
        $totalIncome = 0;
        $totalHours = 0;
        $pendingIncome = 0;
        $pendingHours = 0;

        foreach ($containers as $container) {
            $member = $container->members->where('user_id', $userId)->first();
            if (!$member) {
                continue;
            }

            // Get time entries for this user in this container
            $timeEntries = TimeEntry::where('user_id', $userId)
                ->where('container_id', $container->id)
                ->when($startDate, function ($query) use ($startDate) {
                    return $query->where('start', '>=', $startDate);
                })
                ->when($endDate, function ($query) use ($endDate) {
                    return $query->where('end', '<=', $endDate);
                })
                ->whereNotNull('end')
                ->get();

            $containerPaidTime = 0;
            $containerPaidAmount = 0;
            $containerUnpaidTime = 0;
            $containerUnpaidAmount = 0;

            // Calculate from time entries
            foreach ($timeEntries as $entry) {
                $trackedTime = Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end));
                $trackedHours = $trackedTime / 3600;
                $amount = $trackedHours * $member->billable_rate;

                if ($entry->is_paid) {
                    $containerPaidTime += $trackedTime;
                    $containerPaidAmount += $amount;
                } else {
                    $containerUnpaidTime += $trackedTime;
                    $containerUnpaidAmount += $amount;
                }
            }

            $containerPaidHours = $containerPaidTime / 3600;
            $containerUnpaidHours = $containerUnpaidTime / 3600;

            if ($containerPaidHours > 0 || $containerUnpaidHours > 0) {
                $containerIncomes[] = [
                    'id' => $container->id,
                    'name' => $container->name,
                    'project_name' => $container->project->name,
                    'paid_hours' => round($containerPaidHours, 2),
                    'paid_amount' => round($containerPaidAmount, 2),
                    'unpaid_hours' => round($containerUnpaidHours, 2),
                    'unpaid_amount' => round($containerUnpaidAmount, 2),
                    'total_hours' => round($containerPaidHours + $containerUnpaidHours, 2),
                    'total_amount' => round($containerPaidAmount + $containerUnpaidAmount, 2),
                    'hourly_rate' => $member->billable_rate,
                ];

                $totalIncome += $containerPaidAmount;
                $totalHours += $containerPaidHours;
                $pendingIncome += $containerUnpaidAmount;
                $pendingHours += $containerUnpaidHours;
            }
        }

        return [
            'containers' => $containerIncomes,
            'total_income' => round($totalIncome, 2),
            'total_hours' => round($totalHours, 2),
            'pending_income' => round($pendingIncome, 2),
            'pending_hours' => round($pendingHours, 2),
            'grand_total' => round($totalIncome + $pendingIncome, 2),
        ];
    }

    private function calculateSpendingData($containers, $userId, $startDate, $endDate)
    {
        $containerSpendings = [];
        $totalSpending = 0;
        $pendingSpending = 0;

        foreach ($containers as $container) {
            // Eager load all required relationships for better performance
            $container->load([
                'tasks.timeEntries' => function ($query) use ($startDate, $endDate) {
                    $query->whereNotNull('end');
                    $query->where(function ($query) use ($startDate, $endDate) {
                        if ($startDate) {
                            $query->where('start', '>=', $startDate);
                        }
                        if ($endDate) {
                            $query->where('end', '<=', $endDate);
                        }
                    });
                },
                'members.user',
                'project'
            ]);

            $memberStats = [];
            $containerPaidAmount = 0;
            $containerUnpaidAmount = 0;

            // Process each member's time entries and paychecks
            foreach ($container->members as $member) {
                $memberPaidAmount = 0;
                $memberUnpaidAmount = 0;
                $memberPaidSeconds = 0;
                $memberUnpaidSeconds = 0;

                // Calculate from time entries
                foreach ($container->boards as $board) {
                    foreach ($board->tasks as $task) {
                        $timeEntries = $task->timeEntries->where('user_id', $member->user_id);
                        
                        foreach ($timeEntries as $timeEntry) {
                            $trackedTime = $this->calculateTrackedTime($timeEntry);
                            $trackedHours = $trackedTime / 3600;
                            $amount = $trackedHours * $member->billable_rate;

                            if ($timeEntry->is_paid) {
                                $memberPaidSeconds += $trackedTime;
                                $memberPaidAmount += $timeEntry->amount_paid ?? $amount;
                            } else {
                                $memberUnpaidSeconds += $trackedTime;
                                $memberUnpaidAmount += $amount;
                            }
                        }
                    }
                }

                // Only include members with activity
                if ($memberPaidAmount > 0 || $memberUnpaidAmount > 0) {
                    $memberStats[$member->user_id] = [
                        'user_id' => $member->user_id,
                        'name' => $member->user->full_name,
                        'paid_amount' => round($memberPaidAmount, 2),
                        'paid_hours' => round($memberPaidSeconds / 3600, 2),
                        'unpaid_amount' => round($memberUnpaidAmount, 2),
                        'unpaid_hours' => round($memberUnpaidSeconds / 3600, 2),
                        'hourly_rate' => $member->billable_rate,
                        'total_amount' => round($memberPaidAmount + $memberUnpaidAmount, 2),
                        'total_hours' => round(($memberPaidSeconds + $memberUnpaidSeconds) / 3600, 2),
                    ];

                    $containerPaidAmount += $memberPaidAmount;
                    $containerUnpaidAmount += $memberUnpaidAmount;
                }
            }

            if ($containerPaidAmount > 0 || $containerUnpaidAmount > 0) {
                $containerSpendings[] = [
                    'id' => $container->id,
                    'name' => $container->name,
                    'project_name' => $container->project->name,
                    'paid_amount' => round($containerPaidAmount, 2),
                    'unpaid_amount' => round($containerUnpaidAmount, 2),
                    'total_amount' => round($containerPaidAmount + $containerUnpaidAmount, 2),
                    'members' => array_values($memberStats),
                ];

                $totalSpending += $containerPaidAmount;
                $pendingSpending += $containerUnpaidAmount;
            }
        }

        return [
            'containers' => $containerSpendings,
            'total_spending' => round($totalSpending, 2),
            'pending_spending' => round($pendingSpending, 2),
            'grand_total' => round($totalSpending + $pendingSpending, 2),
        ];
    }

    private function calculateTrackedTime($timeEntry)
    {
        if (!$timeEntry || !$timeEntry->end) {
            return 0;
        }

        $start = Carbon::parse($timeEntry->start);
        $end = Carbon::parse($timeEntry->end);

        return $start->lte($end) ? $start->diffInSeconds($end) : 0;
    }

    private function getActiveProjects($userId)
    {
        $now = Carbon::now();
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Get containers where user is a member or owner
        $containers = Container::where(function($query) use ($userId) {
            $query->where('owner_id', $userId)
                  ->orWhereHas('members', function ($q) use ($userId) {
                      $q->where('user_id', $userId);
                  });
        })
            ->with(['project', 'members.user'])
            ->get();

        $projectActivity = [];

        foreach ($containers as $container) {
            $projectId = $container->project_id;
            
            if (!isset($projectActivity[$projectId])) {
                $projectActivity[$projectId] = [
                    'id' => $projectId,
                    'name' => $container->project->name,
                    'containers' => [],
                    'active_users' => [],
                    'last_activity' => null,
                    'total_hours_this_month' => 0,
                ];
            }

            // Get active time entries (currently running)
            $activeTimeEntries = TimeEntry::whereHas('task', function ($query) use ($container) {
                $query->whereHas('board', function ($query) use ($container) {
                    $query->where('container_id', $container->id);
                });
            })
                ->whereNull('end')
                ->with('user')
                ->get();

            // Get recent time entries
            $recentTimeEntries = TimeEntry::whereHas('task', function ($query) use ($container) {
                $query->whereHas('board', function ($query) use ($container) {
                    $query->where('container_id', $container->id);
                });
            })
                ->whereNotNull('end')
                ->where('end', '>=', $thirtyDaysAgo)
                ->orderBy('end', 'desc')
                ->with('user')
                ->get();

            // Calculate total hours this month
            $thisMonthTimeEntries = TimeEntry::whereHas('task', function ($query) use ($container) {
                $query->whereHas('board', function ($query) use ($container) {
                    $query->where('container_id', $container->id);
                });
            })
                ->whereNotNull('end')
                ->where('start', '>=', Carbon::now()->startOfMonth())
                ->where('end', '<=', Carbon::now()->endOfMonth())
                ->get();

            $totalHoursThisMonth = 0;
            foreach ($thisMonthTimeEntries as $entry) {
                $trackedTime = Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end));
                $totalHoursThisMonth += $trackedTime / 3600;
            }

            // Add container data
            $projectActivity[$projectId]['containers'][] = [
                'id' => $container->id,
                'name' => $container->name,
            ];

            // Add active users
            foreach ($activeTimeEntries as $entry) {
                $duration = 0;
                if ($entry->end) {
                    $duration = Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end));
                } else {
                    $duration = Carbon::parse($entry->start)->diffInSeconds(now());
                }
                
                $projectActivity[$projectId]['active_users'][$entry->user_id] = [
                    'id' => $entry->user_id,
                    'first_name' => $entry->user->first_name,
                    'last_name' => $entry->user->last_name,
                    'avatar' => $entry->user->avatar,
                    'avatar_or_initials' => $entry->user->avatar_or_initials,
                    'started_at' => $entry->start,
                    'duration' => $duration,
                    'container' => [
                        'id' => $entry->task->board->container_id,
                        'name' => $entry->task->board->container->name
                    ]
                ];
            }

            // Update last activity
            if ($recentTimeEntries->isNotEmpty()) {
                $lastEntry = $recentTimeEntries->first();
                $lastActivity = Carbon::parse($lastEntry->end);
                
                if ($projectActivity[$projectId]['last_activity'] === null || 
                    $lastActivity->gt(Carbon::parse($projectActivity[$projectId]['last_activity']))) {
                    $projectActivity[$projectId]['last_activity'] = $lastEntry->end;
                    $projectActivity[$projectId]['last_active_user'] = [
                        'id' => $lastEntry->user_id,
                        'first_name' => $lastEntry->user->first_name,
                        'last_name' => $lastEntry->user->last_name,
                        'avatar' => $lastEntry->user->avatar,
                        'avatar_or_initials' => $lastEntry->user->avatar_or_initials,
                        'container' => [
                            'id' => $lastEntry->task->board->container_id,
                            'name' => $lastEntry->task->board->container->name
                        ]
                    ];
                }
            }

            // Update total hours
            $projectActivity[$projectId]['total_hours_this_month'] += $totalHoursThisMonth;
        }

        // Convert to array and sort by activity
        $result = array_values($projectActivity);
        usort($result, function ($a, $b) {
            // First sort by active users (more active users first)
            $activeUsersA = count($a['active_users']);
            $activeUsersB = count($b['active_users']);
            
            if ($activeUsersA !== $activeUsersB) {
                return $activeUsersB - $activeUsersA;
            }
            
            // Then sort by last activity (more recent first)
            if ($a['last_activity'] === null) return 1;
            if ($b['last_activity'] === null) return -1;
            
            return Carbon::parse($b['last_activity'])->timestamp - Carbon::parse($a['last_activity'])->timestamp;
        });

        // Format the active users as arrays and limit to top 5 projects
        foreach ($result as &$project) {
            $project['active_users'] = array_values($project['active_users']);
            $project['total_hours_this_month'] = round($project['total_hours_this_month'], 2);
        }

        return array_slice($result, 0, 5);
    }

    private function getRecentActivity($userId)
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        // Get recent time entries for containers where user is a member or owner
        $recentTimeEntries = TimeEntry::where(function ($query) use ($userId) {
            $query->whereHas('task.board.container.members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->orWhereHas('task.board.container', function ($q) use ($userId) {
                $q->where('owner_id', $userId);
            });
        })
            ->with(['user', 'task.board.container.project'])
            ->whereNotNull('end')
            ->where('end', '>=', $thirtyDaysAgo)
            ->orderBy('end', 'desc')
            ->limit(10)
            ->get();

        $activities = [];
        
        foreach ($recentTimeEntries as $entry) {
            $activities[] = [
                'id' => $entry->id,
                'type' => 'time_entry',
                'user' => [
                    'id' => $entry->user_id,
                    'name' => $entry->user->full_name,
                    'avatar' => $entry->user->avatar,
                    'avatar_or_initials' => $entry->user->avatar_or_initials,
                ],
                'task' => [
                    'id' => $entry->task_id,
                    'name' => $entry->task->name,
                    'sequence_id' => $entry->task->sequence_id,
                ],
                'board' => [
                    'id' => $entry->task->board_id,
                    'name' => $entry->task->board->name,
                ],
                'container' => [
                    'id' => $entry->task->board->container_id,
                    'name' => $entry->task->board->container->name,
                ],
                'project' => [
                    'id' => $entry->task->board->container->project_id,
                    'name' => $entry->task->board->container->project->name,
                ],
                'duration' => Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end)),
                'duration_display' => $this->formatTime(Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))),
                'start' => $entry->start,
                'end' => $entry->end,
                'is_paid' => $entry->is_paid,
                'amount' => $entry->amount_paid,
            ];
        }

        return $activities;
    }

    private function formatTime($seconds = 0): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
} 