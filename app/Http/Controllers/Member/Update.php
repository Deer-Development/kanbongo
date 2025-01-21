<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Member\ValidateMemberUpdate;
use App\Services\Member\MemberService;
use App\Http\Resources\Member\MemberResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(MemberService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateMemberUpdate $request, int $id): JsonResponse
    {
        $model = $this->service->update($id, $request->validated());

        return $this->successResponse(new MemberResource($model), 'Member updated successfully.', Response::HTTP_OK);
    }
}
