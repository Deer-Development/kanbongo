<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Container\ValidateContainerStore;
use App\Services\Container\ContainerService;
use App\Http\Resources\Container\ContainerResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateContainerStore $request): JsonResponse
    {
        $model = $this->service->create(
            array_merge(
                $request->validated(),
                ['owner_id' => auth()->id()]
            )
        );

        return $this->successResponse(new ContainerResource($model), 'Container created successfully.', Response::HTTP_CREATED);
    }
}
