<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Container\ContainerResource;
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
        $startDate = null;
        $endDate = null;
        $isSuperAdmin = filter_var($request->input('is_super_admin'), FILTER_VALIDATE_BOOLEAN);
        $isOwner = filter_var($request->input('is_owner'), FILTER_VALIDATE_BOOLEAN);
        $authUserId = Auth::id();

        if ($dateRange) {
            [$start, $end] = array_pad(explode(' to ', $dateRange), 2, null);
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = $end ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
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
            'members' => function ($q) use ($isSuperAdmin, $isOwner, $authUserId) {
                if (!$isSuperAdmin && !$isOwner) {
                    $q->where('user_id', $authUserId);
                }
                $q->with('user');
            }
        ])->findOrFail($id);

        $paymentDetails = [];

        foreach ($model->members as $member) {
            $totalSecondsWorked = 0;

            foreach ($model->boards as $board) {
                foreach ($board->tasks as $task) {
                    foreach ($task->timeEntries as $timeEntry) {
                        if ($timeEntry->user_id === $member->user_id) {
                            $trackedTime = $this->calculateTrackedTime($timeEntry);
                            $totalSecondsWorked += $trackedTime;
                        }
                    }
                }
            }

            $totalHoursWorked = $totalSecondsWorked / 3600;
            $totalPayment = $totalHoursWorked * $member->billable_rate;

            $paymentDetails[] = [
                'member_id' => $member->id,
                'member_name' => $member->user->full_name ?? 'N/A',
                'user' => $member->user,
                'total_seconds_worked' => $totalSecondsWorked,
                'total_hours_worked' => round($totalHoursWorked, 2),
                'billable_rate' => $member->billable_rate,
                'total_payment' => round($totalPayment, 2),
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
}

