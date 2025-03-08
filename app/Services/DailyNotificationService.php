<?php

namespace App\Services;

use App\Models\User;
use App\Models\Container;
use App\Models\TimeEntry;
use App\Http\Controllers\DashboardController;
use App\Notifications\DailyMemberActivityNotification;
use App\Notifications\DailyOwnerActivityNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\SpendingCalculatorService;

class DailyNotificationService
{
    private $spendingCalculator;

    public function __construct(SpendingCalculatorService $spendingCalculator)
    {
        $this->spendingCalculator = $spendingCalculator;
    }

    public function sendDailyNotifications(): void
    {
        $today = Carbon::now()->startOfDay();
        $tomorrow = Carbon::now()->endOfDay();

        // Get all users that either:
        // 1. Have tracked time today (as members)
        // 2. Own containers where time was tracked today
        // 3. Both of the above
        $users = User::where(function ($query) use ($today, $tomorrow) {
            // Users who tracked time today
            $query->whereHas('timeEntries', function ($q) use ($today, $tomorrow) {
                $q->whereBetween('start', [$today, $tomorrow]);
            });
        })->orWhere(function ($query) use ($today, $tomorrow) {
            // Users who own containers where time was tracked today
            $query->whereHas('ownedContainers', function ($q) use ($today, $tomorrow) {
                $q->whereHas('timeEntries', function ($sq) use ($today, $tomorrow) {
                    $sq->whereBetween('start', [$today, $tomorrow]);
                });
            });
        })->get();

        foreach ($users as $user) {
            try {
                $this->sendNotificationsForUser($user, $today, $tomorrow);
            } catch (\Exception $e) {
                Log::error("Failed to send daily notification to user {$user->id}: " . $e->getMessage());
            }
        }
    }

    private function sendNotificationsForUser(User $user, Carbon $start, Carbon $end): void
    {
        // Check and send member notification
        $hasMemberActivity = TimeEntry::where('user_id', $user->id)
            ->whereBetween('start', [$start, $end])
            ->exists();

        if ($hasMemberActivity) {
            $memberData = $this->getMemberActivityData($user, $start, $end);
            if (!empty($memberData['containers'])) {
                $user->notify(new DailyMemberActivityNotification($memberData));
            }
        }

        // Check and send owner notification
        $hasOwnerActivity = Container::where('owner_id', $user->id)
            ->whereHas('timeEntries', function ($query) use ($start, $end) {
                $query->whereBetween('start', [$start, $end]);
            })->exists();

        if ($hasOwnerActivity) {
            $ownerData = $this->getOwnerActivityData($user, $start, $end);
            if (!empty($ownerData['containers'])) {
                $user->notify(new DailyOwnerActivityNotification($ownerData));
            }
        }
    }

    private function getMemberActivityData(User $user, Carbon $start, Carbon $end): array
    {
        $containers = Container::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['members', 'project'])->get();

        $data = [
            'containers' => [],
            'total_hours' => 0,
            'total_income' => 0
        ];

        foreach ($containers as $container) {
            $member = $container->members->where('user_id', $user->id)->first();
            if (!$member) continue;

            $timeEntries = TimeEntry::where('user_id', $user->id)
                ->where('container_id', $container->id)
                ->whereBetween('start', [$start, $end])
                ->whereNotNull('end')
                ->get();

            $containerHours = 0;
            $containerAmount = 0;

            foreach ($timeEntries as $entry) {
                $trackedTime = Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end));
                $trackedHours = $trackedTime / 3600;
                $amount = $trackedHours * $member->billable_rate;

                $containerHours += $trackedHours;
                $containerAmount += $amount;
            }

            if ($containerHours > 0) {
                $data['containers'][] = [
                    'project_name' => $container->project->name,
                    'total_hours' => round($containerHours, 2),
                    'total_amount' => round($containerAmount, 2)
                ];

                $data['total_hours'] += $containerHours;
                $data['total_income'] += $containerAmount;
            }
        }

        $data['total_hours'] = round($data['total_hours'], 2);
        $data['total_income'] = round($data['total_income'], 2);

        return $data;
    }

    private function getOwnerActivityData(User $user, Carbon $start, Carbon $end): array
    {
        $containers = Container::where('owner_id', $user->id)
            ->with(['members.user', 'project', 'timeEntries' => function ($query) use ($start, $end) {
                $query->whereBetween('start', [$start, $end]);
            }])
            ->whereHas('timeEntries', function ($query) use ($start, $end) {
                $query->whereBetween('start', [$start, $end]);
            })
            ->get();

        return $this->spendingCalculator->calculateSpendingData(
            $containers,
            $user->id,
            $start,
            $end
        );
    }
} 