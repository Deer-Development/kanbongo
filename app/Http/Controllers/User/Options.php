<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\User\UserResource;

class Options extends BaseController
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->getOptions($request);

        return $this->successResponse(UserResource::collection($items), 'User list retrieved successfully.');
    }
}
