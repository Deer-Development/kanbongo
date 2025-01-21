<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\BaseController;
use App\Services\Member\MemberService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected MemberService $service;

    public function __construct(MemberService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'Member deleted successfully.', Response::HTTP_OK);
    }
}
