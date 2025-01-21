<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Services\User\UserService;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new UserResource($model), 'User details fetched successfully.');
    }
}
