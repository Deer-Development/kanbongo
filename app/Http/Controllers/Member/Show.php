<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\BaseController;
use App\Services\Member\MemberService;
use App\Http\Resources\Member\MemberResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(MemberService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new MemberResource($model), 'Member details fetched successfully.');
    }
}
