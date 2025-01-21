<?php

namespace App\Http\Controllers\Total;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Total\ValidateTotalStore;
use App\Services\Total\TotalService;
use App\Http\Resources\Total\TotalResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(TotalService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTotalStore $request): JsonResponse
    {
        $model = $this->service->create($request->validated());

        return $this->successResponse(new TotalResource($model), 'Total created successfully.', Response::HTTP_CREATED);
    }
}
