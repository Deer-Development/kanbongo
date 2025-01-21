<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Container\ValidateContainerUpdate;
use App\Services\Container\ContainerService;
use App\Http\Resources\Container\ContainerResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateContainerUpdate $request, int $id): JsonResponse
    {
        $model = $this->service->update($id, $request->validated());

        return $this->successResponse(new ContainerResource($model), 'Container updated successfully.', Response::HTTP_OK);
    }
}
