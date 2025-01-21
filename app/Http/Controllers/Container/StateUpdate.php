<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Board\BoardResource;
use App\Http\Resources\Container\ContainerResource;
use App\Services\Container\ContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StateUpdate extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $model = $this->service->updateState($id, $request->all());

        return $this->successResponse(new ContainerResource($model), 'Container updated successfully.', Response::HTTP_OK);
    }
}
