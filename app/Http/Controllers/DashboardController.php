<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\TimeEntry;
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
        $period = $request->get('period', 'current_month');
        $dateRange = null;

        if ($period === 'custom') {
            $dateRange = $request->get('date_range');
            if (!$dateRange || count($dateRange) !== 2) {
                return $this->errorResponse('Invalid date range provided', 422);
            }
            
            $startDate = Carbon::parse($dateRange[0])->startOfDay();
            $endDate = Carbon::parse($dateRange[1])->endOfDay();
        } else {
            $dateRange = $this->getDateRangeForPeriod($period);
            if (!$dateRange) {
                return $this->errorResponse('Invalid period provided', 422);
            }
            
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];
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
        $activeProjects = $this->getActiveProjects($user->id, $startDate, $endDate);

        // Get recent activity
        $recentActivity = $this->getRecentActivity($user->id, $startDate, $endDate);

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
                ->where('start', '>=', $startDate)
                ->where('end', '<=', $endDate)
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
                    $totalIncome += $amount;
                    $totalHours += $trackedHours;
                } else {
                    $containerUnpaidTime += $trackedTime;
                    $containerUnpaidAmount += $amount;
                    $pendingIncome += $amount;
                    $pendingHours += $trackedHours;
                }
            }

            // Calculate total hours for this period
            $totalHoursThisMonth = 0;
            foreach ($timeEntries as $entry) {
                $trackedTime = Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end));
                $totalHoursThisMonth += $trackedTime / 3600;
            }

            // Skip containers with no activity
            if ($containerPaidTime === 0 && $containerUnpaidTime === 0) {
                continue;
            }

            $containerIncomes[] = [
                'id' => $container->id,
                'name' => $container->name,
                'project' => $container->project,
                'project_name' => $container->project->name,
                'paid_time' => $this->formatTime($containerPaidTime),
                'paid_amount' => $containerPaidAmount,
                'unpaid_time' => $this->formatTime($containerUnpaidTime),
                'unpaid_amount' => $containerUnpaidAmount,
                'total_hours' => round($totalHoursThisMonth, 2),
                'total_amount' => $containerPaidAmount + $containerUnpaidAmount
            ];
        }

        return [
            'containers' => $containerIncomes,
            'total_income' => $totalIncome,
            'total_hours' => $totalHours,
            'pending_income' => $pendingIncome,
            'pending_hours' => $pendingHours,
            'grand_total' => $totalIncome + $pendingIncome
        ];
    }

    private function calculateSpendingData($containers, $userId, $startDate, $endDate)
    {
        $containerSpendings = [];
        $totalSpending = 0;
        $pendingSpending = 0;

        foreach ($containers as $container) {
            $timeEntries = TimeEntry::whereHas('task', function ($query) use ($container) {
                $query->whereHas('board', function ($query) use ($container) {
                    $query->where('container_id', $container->id);
                });
            })
                ->where('start', '>=', $startDate)
                ->where('end', '<=', $endDate)
                ->whereNotNull('end')
                ->get();

            $containerPaidTime = 0;
            $containerPaidAmount = 0;
            $containerUnpaidTime = 0;
            $containerUnpaidAmount = 0;
            $memberStats = [];

            foreach ($timeEntries as $entry) {
                $member = $container->members->where('user_id', $entry->user_id)->first();
                if (!$member) continue;

                $trackedTime = Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end));
                $trackedHours = $trackedTime / 3600;
                $amount = $trackedHours * $member->billable_rate;

                // Initialize member stats if not exists
                if (!isset($memberStats[$member->user_id])) {
                    $memberStats[$member->user_id] = [
                        'user_id' => $member->user_id,
                        'name' => $member->user->full_name,
                        'paid_time' => 0,
                        'paid_amount' => 0,
                        'unpaid_time' => 0,
                        'unpaid_amount' => 0,
                        'hourly_rate' => $member->billable_rate,
                        'total_time' => 0,
                        'total_amount' => 0
                    ];
                }

                if ($entry->is_paid) {
                    $containerPaidTime += $trackedTime;
                    $containerPaidAmount += $amount;
                    $totalSpending += $amount;
                    $memberStats[$member->user_id]['paid_time'] += $trackedTime;
                    $memberStats[$member->user_id]['paid_amount'] += $amount;
                } else {
                    $containerUnpaidTime += $trackedTime;
                    $containerUnpaidAmount += $amount;
                    $pendingSpending += $amount;
                    $memberStats[$member->user_id]['unpaid_time'] += $trackedTime;
                    $memberStats[$member->user_id]['unpaid_amount'] += $amount;
                }

                $memberStats[$member->user_id]['total_time'] += $trackedTime;
                $memberStats[$member->user_id]['total_amount'] += $amount;
            }

            // Format member stats
            foreach ($memberStats as &$stats) {
                $stats['paid_time'] = $this->formatTime($stats['paid_time']);
                $stats['unpaid_time'] = $this->formatTime($stats['unpaid_time']);
                $stats['total_time'] = $this->formatTime($stats['total_time']);
                $stats['paid_amount'] = round($stats['paid_amount'], 2);
                $stats['unpaid_amount'] = round($stats['unpaid_amount'], 2);
                $stats['total_amount'] = round($stats['total_amount'], 2);
            }

            // Calculate total hours for this period
            $totalHoursThisPeriod = 0;
            foreach ($timeEntries as $entry) {
                $trackedTime = Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end));
                $totalHoursThisPeriod += $trackedTime / 3600;
            }

            // Skip containers with no activity
            if ($containerPaidTime === 0 && $containerUnpaidTime === 0) {
                continue;
            }

            $containerSpendings[] = [
                'id' => $container->id,
                'name' => $container->name,
                'project' => $container->project,
                'project_name' => $container->project->name,
                'paid_time' => $this->formatTime($containerPaidTime),
                'paid_amount' => $containerPaidAmount,
                'unpaid_time' => $this->formatTime($containerUnpaidTime),
                'unpaid_amount' => $containerUnpaidAmount,
                'total_hours_this_period' => round($totalHoursThisPeriod, 2),
                'total_amount' => $containerPaidAmount + $containerUnpaidAmount,
                'members' => array_values($memberStats)
            ];
        }

        return [
            'containers' => $containerSpendings,
            'total_spending' => $totalSpending,
            'pending_spending' => $pendingSpending,
            'grand_total' => $totalSpending + $pendingSpending
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

    private function getActiveProjects($userId, $startDate, $endDate)
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
            $isOwner = $container->owner_id === $userId;

            if (!isset($projectActivity[$projectId])) {
                $projectActivity[$projectId] = [
                    'id' => $projectId,
                    'name' => $container->project?->name,
                    'is_owner' => $isOwner,
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
            $thisMonthTimeEntries = TimeEntry::whereHas('task', function ($query) use ($container, $startDate, $endDate) {
                $query->whereHas('board', function ($query) use ($container, $startDate, $endDate) {
                    $query->where('container_id', $container->id);
                });
            })
                ->when(!$isOwner, function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->where('start', '>=', $startDate)
                ->where('end', '<=', $endDate)
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
                    'task' => [
                        'id' => $entry->task_id,
                        'name' => $entry->task->name,
                    ],
                    'board' => [
                        'id' => $entry->task->board_id,
                        'name' => $entry->task->board->name,
                    ],
                    'container' => [
                        'id' => $entry->task->board->container_id,
                        'name' => $entry->task->board->container->name
                    ],
                    'project' => [
                        'id' => $entry->task->board->container->project_id,
                        'name' => $entry->task->board->container->project->name,
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

    private function getRecentActivity($userId, $startDate, $endDate)
    {
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
            ->where('end', '>=', $startDate)
            ->where('end', '<=', $endDate)
            ->orderBy('end', 'desc')
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

    private function getDateRangeForPeriod($period)
    {
        $now = Carbon::now();
        
        return match ($period) {
            'current_week' => [
                'start' => $now->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'current_month' => [
                'start' => $now->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'current_quarter' => [
                'start' => $now->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'current_year' => [
                'start' => $now->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'last_week' => [
                'start' => $now->subWeek()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'last_month' => [
                'start' => $now->subMonth()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_quarter' => [
                'start' => $now->subQuarter()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'last_year' => [
                'start' => $now->subYear()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => null,
        };
    }
} 