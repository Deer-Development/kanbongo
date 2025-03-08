<?php

namespace App\Services;

use App\Models\TimeEntry;
use Carbon\Carbon;

class SpendingCalculatorService
{
    public function calculateSpendingData($containers, $userId, $startDate, $endDate)
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

            $totalHoursThisPeriod = array_sum(array_map(function($entry) {
                return Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end)) / 3600;
            }, $timeEntries->all()));

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

    private function formatTime($seconds = 0): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
} 