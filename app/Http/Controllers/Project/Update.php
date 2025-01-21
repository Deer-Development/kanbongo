<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Project\ValidateProjectUpdate;
use App\Services\Project\ProjectService;
use App\Http\Resources\Project\ProjectResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateProjectUpdate $request, int $id): JsonResponse
    {
        $model = $this->service->update($id, $request->validated());

        return $this->successResponse(new ProjectResource($model), 'Project updated successfully.', Response::HTTP_OK);
    }
}
