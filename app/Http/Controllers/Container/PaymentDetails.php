<?php

namespace App\Http\Controllers\Container;

use App\Enums\PaymentType;
use App\Enums\SalaryPaymentTypes;
use App\Http\Controllers\BaseController;
use App\Models\Container;
use App\Services\Payment\PaymentDateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentDetails extends BaseController
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $dateRange = $request->input('date_range');
        $paymentStatus = $request->input('payment_status', 'all');
        $startDate = null;
        $endDate = null;
        $isSuperAdmin = filter_var($request->input('is_super_admin'), FILTER_VALIDATE_BOOLEAN);
        $isOwner = filter_var($request->input('is_owner'), FILTER_VALIDATE_BOOLEAN);
        $isAdmin = filter_var($request->input('is_admin'), FILTER_VALIDATE_BOOLEAN);
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
            'members' => function ($q) use ($isSuperAdmin, $isOwner, $authUserId, $isAdmin, $id) {
                if (!$isSuperAdmin && !$isOwner && !$isAdmin) {
                    $q->where('user_id', $authUserId);
                }
                $q->with(['user.paychecks' => function ($q) use ($id): void {
                    $q->orderBy('created_at', 'desc');
                    $q->where('container_id', $id);
                }]);
            }
        ])->findOrFail($id);

        $paymentDetails = [];

        foreach ($model->members as $member) {
            $memberDetails = [
                'member_id' => $member->id,
                'member_name' => $member->user->full_name ?? 'N/A',
                'user' => $member->user,
                'payment_type' => $member->payment_type,
                'payment_type_name' => PaymentType::getName($member->payment_type),
                'has_paychecks' => $member->user->paychecks->where('container_id', $id)->count() > 0,
                'last_payment_date' => $member->last_payment_date,
                'last_target_payment_date' => $member->last_target_payment_date,
            ];


            $nextPaymentDate = PaymentDateCalculator::getNextPaymentDate($member->salary_payment_type);

            // Check if salary has been paid for the current period
            $currentPeriodPaid = $member->user->paychecks->where('container_id', $id)->contains(function ($paycheck) use ($member, $nextPaymentDate) {
            
                $lastPaymentTargetDate = $member->last_target_payment_date ? Carbon::parse($member->last_target_payment_date)->format('Y-m-d') : null;
                return $lastPaymentTargetDate && $nextPaymentDate &&
                       $lastPaymentTargetDate === Carbon::parse($nextPaymentDate)->format('Y-m-d');
            });

            $memberDetails['current_period_paid'] = $currentPeriodPaid;
            $memberDetails['current_period_amount'] = $currentPeriodPaid ? $member->user->paychecks->where('container_id', $id)
            ->where('target_payment_date', $nextPaymentDate->format('Y-m-d'))->first()?->total_amount : 0;

            // Calculate next payment date dynamically based on actual payment date
            $actualNextPaymentDate = $currentPeriodPaid
                ? PaymentDateCalculator::getNextNextPaymentDate($member->salary_payment_type)
                : $nextPaymentDate;

            $memberDetails['next_payment_date'] = $actualNextPaymentDate->format('Y-m-d');
            $memberDetails['last_payment_date'] = $currentPeriodPaid ? $member->user->paychecks->where('container_id', $id)
            ->where('target_payment_date', $nextPaymentDate->format('Y-m-d'))->first()?->created_at : null;

            // Handle different payment types
            switch ($member->payment_type) {
                case PaymentType::HOURLY:
                    $memberDetails = $this->calculateHourlyPayment($memberDetails, $member, $model, $paymentStatus);
                    break;

                case PaymentType::SALARY:
                    $memberDetails = $this->calculateSalaryPayment($memberDetails, $member, $id);
                    break;

                case PaymentType::NO_PAYMENT:
                    $memberDetails['payment_status'] = 'No payment required';
                    $memberDetails['next_payment_date'] = null;
                    break;
            }


            $paymentDetails[] = $memberDetails;
        }

        return $this->successResponse($paymentDetails, 'Payment details fetched successfully.');
    }

    private function calculateHourlyPayment(array $memberDetails, $member, $model, $paymentStatus): array
    {
        $totalPaidSeconds = 0;
        $totalUnpaidSeconds = 0;
        $totalAmountPaid = 0;

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

        return array_merge($memberDetails, [
            'total_paid_seconds' => $totalPaidSeconds,
            'total_paid_hours' => round($totalPaidHours, 2),
            'total_amount_paid' => round($totalAmountPaid, 2),
            'total_unpaid_seconds' => $totalUnpaidSeconds,
            'total_unpaid_hours' => round($totalUnpaidHours, 2),
            'billable_rate' => $member->billable_rate,
            'salary_payment_type_name' => SalaryPaymentTypes::getName($member->salary_payment_type),
            'pending_payment' => round($pendingPayment, 2),
            'payment_status' => 'Hourly rate: $' . $member->billable_rate . '/hour',
        ]);
    }

    private function calculateSalaryPayment(array $memberDetails, $member, $id): array
    {
        $totalSalaryPaid = $member->user->paychecks
            ->where('container_id', $id)
            ->where('payment_type', PaymentType::SALARY)
            ->sum('total_amount');
        
        return array_merge($memberDetails, [
            'salary' => $member->salary,
            'salary_payment_type' => $member->salary_payment_type,
            'salary_payment_type_name' => SalaryPaymentTypes::getName($member->salary_payment_type),
            'payment_status' => 'Salary: $' . number_format($member->salary, 2) . ' ' . strtolower(SalaryPaymentTypes::getName($member->salary_payment_type)),
            'total_salary_paid' => round($totalSalaryPaid, 2),
        ]);
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
            'all_time' => [
                'start' => null,
                'end' => null,
            ],
            default => null,
        };
    }
}
