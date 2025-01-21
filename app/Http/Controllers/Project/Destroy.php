<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\BaseController;
use App\Services\Project\ProjectService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected ProjectService $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'Project deleted successfully.', Response::HTTP_OK);
    }
}
