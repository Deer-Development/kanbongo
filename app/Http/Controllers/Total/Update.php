<?php

namespace App\Http\Controllers\Total;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Total\ValidateTotalUpdate;
use App\Services\Total\TotalService;
use App\Http\Resources\Total\TotalResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(TotalService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTotalUpdate $request, int $id): JsonResponse
    {
        $model = $this->service->update($id, $request->validated());

        return $this->successResponse(new TotalResource($model), 'Total updated successfully.', Response::HTTP_OK);
    }
}
