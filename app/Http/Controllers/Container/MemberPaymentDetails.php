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
        $startDate = null;
        $endDate = null;

        if ($dateRange) {
            [$start, $end] = array_pad(explode(' to ', $dateRange), 2, null);
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = $end ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
        }

        $model = Container::with([
            'timeEntries' => function ($q) use ($userId, $startDate, $endDate) {
                $q->where('user_id', $userId);
                if ($startDate) {
                    $q->where('start', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('end', '<=', $endDate);
                }
                $q->with('user:id,first_name,last_name,email');
                $q->with('task:id,name');
                $q->with('logs');
            },
        ])->findOrFail($id);

        $paymentDetails = $model->timeEntries
            ->whereNotNull('end')
            ->groupBy('task_id')
            ->map(function ($entries) {
                $trackedTime = $entries->sum(fn($entry) => $entry->end
                    ? Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))
                    : 0);

                return [
                    'task' => $entries->first()->task,
                    'trackedTime' => $trackedTime,
                    'trackedTimeDisplay' => $this->formatTime($trackedTime),
                    'timeEntries' => $entries
                        ->filter(fn($entry) => !is_null($entry->end))
                        ->map(fn($entry) => [
                            'id' => $entry->id,
                            'start' => $entry->start,
                            'end' => $entry->end,
                            'duration' => $this->formatTime(Carbon::parse($entry->start)->diffInSeconds(Carbon::parse($entry->end))),
                            'is_paid' => $entry->is_paid,
                            'amount_paid' => $entry->amount_paid,
                            'paid_rate' => $entry->paid_rate,
                            'added_manually' => $entry->added_manually,
                        ])->values(),
                ];
            });

        return $this->successResponse($paymentDetails, 'Payment details fetched successfully.');
    }

    private function formatTime($seconds = 0)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
