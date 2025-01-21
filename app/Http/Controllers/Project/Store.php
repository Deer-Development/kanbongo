<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Project\ValidateProjectStore;
use App\Services\Project\ProjectService;
use App\Http\Resources\Project\ProjectResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateProjectStore $request): JsonResponse
    {
        $model = $this->service->create(
            array_merge(
                $request->validated(),
                ['owner_id' => auth()->id()]
            )
        );

        return $this->successResponse(new ProjectResource($model), 'Project created successfully.', Response::HTTP_CREATED);
    }
}
