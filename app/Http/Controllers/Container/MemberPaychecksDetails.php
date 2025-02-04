<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Models\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MemberPaychecksDetails extends BaseController
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
            'paychecks' => function ($q) use ($userId, $startDate, $endDate) {
                $q->where('user_id', $userId);
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('created_at', '<=', $endDate);
                }
                $q->with(['createdBy', 'project', 'container']);
            },
        ])->findOrFail($id);


        $paychecksDetails = $model->paychecks;

        return $this->successResponse($paychecksDetails, 'Payment details fetched successfully.');
    }
}
