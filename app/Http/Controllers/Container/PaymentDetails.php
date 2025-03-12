<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Models\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PaymentDetails extends BaseController
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $dateRange = $request->input('date_range');
        $periodPreset = $request->input('period_preset', 'current_month');
        $paymentStatus = $request->input('payment_status', 'all');
        $startDate = null;
        $endDate = null;
        $isSuperAdmin = filter_var($request->input('is_super_admin'), FILTER_VALIDATE_BOOLEAN);
        $isOwner = filter_var($request->input('is_owner'), FILTER_VALIDATE_BOOLEAN);
        $isAdmin = filter_var($request->input('is_admin'), FILTER_VALIDATE_BOOLEAN);
        $authUserId = Auth::id();

        if ($dateRange) {
            // Handle custom date range
            [$start, $end] = array_pad(explode(' to ', $dateRange), 2, null);
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = $end ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
        } else {
            // Use period preset
            $dateRange = $this->getDateRangeForPeriod($periodPreset);
            if ($dateRange) {
                $startDate = $dateRange['start'];
                $endDate = $dateRange['end'];
            } else {
                // Fallback to current month
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
            }
        }

        $model = Container::with([
            'boards' => function ($q) use ($startDate, $endDate) {
                $q->orderBy('created_at')->with([
                    'tasks' => function ($q) use ($startDate, $endDate) {
                        $q->with([
                            'timeEntries' => function ($q) use ($startDate, $endDate) {
                                if ($startDate) {
                                    $q->where('start', '>=', $startDate);
                                }
                                if ($endDate) {
                                    $q->where('end', '<=', $endDate);
                                }
                            },
                            'members'
                        ]);
                    }
                ]);
            },
            'members' => function ($q) use ($isSuperAdmin, $isOwner, $authUserId, $isAdmin) {
                if (!$isSuperAdmin && !$isOwner && !$isAdmin) {
                    $q->where('user_id', $authUserId);
                }
                $q->with('user.paychecks');
            }
        ])->findOrFail($id);

        $paymentDetails = [];

        foreach ($model->members as $member) {
            $totalPaidSeconds = 0;
            $totalUnpaidSeconds = 0;
            $totalAmountPaid = 0;
            $pendingPayment = 0;

            foreach ($model->boards as $board) {
                foreach ($board->tasks as $task) {
                    foreach ($task->timeEntries as $timeEntry) {
                        if ($timeEntry->user_id === $member->user_id) {
                            if ($paymentStatus !== 'all') {
                                if ($paymentStatus === 'paid' && !$timeEntry->is_paid) continue;
                                if ($paymentStatus === 'pending' && $timeEntry->is_paid) continue;
                            }

                            $trackedTime = $this->calculateTrackedTime($timeEntry);

                            if ($timeEntry->is_paid) {
                                $totalPaidSeconds += $trackedTime;
                                $totalAmountPaid += $timeEntry->amount_paid;
                            } else {
                                $totalUnpaidSeconds += $trackedTime;
                            }
                        }
                    }
                }
            }

            $totalPaidHours = $totalPaidSeconds / 3600;
            $totalUnpaidHours = $totalUnpaidSeconds / 3600;
            $pendingPayment = $totalUnpaidHours * $member->billable_rate;

            $paymentDetails[] = [
                'member_id' => $member->id,
                'member_name' => $member->user->full_name ?? 'N/A',
                'user' => $member->user,
                'has_paychecks' => $member->user->paychecks->isNotEmpty(),
                'total_paid_seconds' => $totalPaidSeconds,
                'total_paid_hours' => round($totalPaidHours, 2),
                'total_amount_paid' => round($totalAmountPaid, 2),
                'total_unpaid_seconds' => $totalUnpaidSeconds,
                'total_unpaid_hours' => round($totalUnpaidHours, 2),
                'billable_rate' => $member->billable_rate,
                'pending_payment' => round($pendingPayment, 2),
            ];
        }

        return $this->successResponse($paymentDetails, 'Payment details fetched successfully.');
    }

    private function calculateTrackedTime($timeEntry)
    {
        if (!$timeEntry || !$timeEntry->end) {
            return 0;
        }

        $start = Carbon::parse($timeEntry->start);
        $end = Carbon::parse($timeEntry->end) ?? now();

        return $start->lte($end) ? $start->diffInSeconds($end) : 0;
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
