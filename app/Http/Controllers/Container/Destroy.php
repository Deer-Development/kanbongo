<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Services\Container\ContainerService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected ContainerService $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'Container deleted successfully.', Response::HTTP_OK);
    }
}
