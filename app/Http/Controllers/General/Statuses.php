<?php

namespace App\Http\Controllers\General;

use App\Enums\Priority;
use App\Http\Controllers\BaseController;
use App\Services\Member\MemberService;
use App\Http\Resources\Member\MemberResource;
use Illuminate\Http\JsonResponse;

class Statuses extends BaseController
{
    public function priority(): JsonResponse
    {
        $priorities = Priority::collection();

        return $this->successResponse($priorities, 'Priorities fetched successfully.');
    }
}
