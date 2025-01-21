<?php

namespace App\Http\Controllers\Total;

use App\Http\Controllers\BaseController;
use App\Services\Total\TotalService;
use App\Http\Resources\Total\TotalResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(TotalService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new TotalResource($model), 'Total details fetched successfully.');
    }
}
