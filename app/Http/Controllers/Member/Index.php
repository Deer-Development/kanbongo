<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\BaseController;
use App\Services\Member\MemberService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Member\MemberResource;

class Index extends BaseController
{
    protected MemberService $service;

    public function __construct(MemberService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request);

        return $this->successResponse([
            'items' => MemberResource::collection($items->items()),
            'totalPages' => $items->lastPage(),
            'totalItems' => $items->total(),
            'page' => $items->currentPage(),
        ], 'Member list retrieved successfully.');
    }
}
