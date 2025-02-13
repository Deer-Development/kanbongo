<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\TimeEntry;
use App\Models\Member;
use Illuminate\Support\Facades\Log;

class CheckWeeklyTimeLimits extends Command
{
    protected $signature = 'time-tracker:check-limits';
    protected $description = 'Automatically stop active time entries if users exceed their weekly limit';

    public function handle()
    {
        $startOfWeek = Carbon::now()->startOfWeek()->startOfDay();
        $endOfWeek = Carbon::now()->endOfWeek()->endOfDay();

        // Get all active time entries (entries without an "end" timestamp)
        $activeTimeEntries = TimeEntry::with('container')->whereNull('end')->get();

        foreach ($activeTimeEntries as $entry) {
            $user = $entry->user;
            $boardMember = $entry->container->members()->where('user_id', $user->id)->first();

            if (!$boardMember || !$boardMember->weekly_limit_enabled) {
                continue; // Skip if no limit is set for this user
            }

            $weeklyLimitSeconds = $boardMember->weekly_limit_hours * 3600;

            // **Calculate total tracked time for this week using the same logic from ContainerResource**
            $weeklyTrackedSeconds = TimeEntry::where('user_id', $user->id)
                ->whereNotNull('end')
                ->where(function ($query) use ($startOfWeek, $endOfWeek) {
                    $query->whereBetween('start', [$startOfWeek, $endOfWeek])
                        ->orWhereBetween('end', [$startOfWeek, $endOfWeek])
                        ->orWhere(function ($q) use ($startOfWeek, $endOfWeek) {
                            $q->where('start', '<', $startOfWeek)
                                ->where('end', '>', $endOfWeek);
                        });
                })
                ->get()
                ->sum(function ($entry) use ($startOfWeek, $endOfWeek) {
                    $entryStart = Carbon::parse($entry->start);
                    $entryEnd = Carbon::parse($entry->end);

                    // Adjust start & end to fit within the weekly timeframe
                    if ($entryStart < $startOfWeek) {
                        $entryStart = $startOfWeek;
                    }
                    if ($entryEnd > $endOfWeek) {
                        $entryEnd = $endOfWeek;
                    }

                    return $entryStart->diffInSeconds($entryEnd);
                });

            // **Now add the time from the currently running time entry**
            $currentSessionSeconds = Carbon::parse($entry->start)->diffInSeconds(Carbon::now());

            $totalProjectedTime = $weeklyTrackedSeconds + $currentSessionSeconds;

            if ($totalProjectedTime >= $weeklyLimitSeconds) {
                // Stop tracking if the limit is exceeded
                $entry->update(['end' => Carbon::now(), 'stopped_by_system' => true]);

                Log::info("Time entry {$entry->id} for user {$user->id} was automatically stopped due to weekly limit.");
            }
        }

        $this->info("Checked and enforced weekly time limits.");
    }
}

