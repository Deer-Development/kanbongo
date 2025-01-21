<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Member\ValidateMemberStore;
use App\Services\Member\MemberService;
use App\Http\Resources\Member\MemberResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(MemberService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateMemberStore $request): JsonResponse
    {
        $model = $this->service->create($request->validated());

        return $this->successResponse(new MemberResource($model), 'Member created successfully.', Response::HTTP_CREATED);
    }
}
