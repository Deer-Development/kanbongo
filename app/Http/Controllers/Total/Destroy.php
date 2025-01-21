<?php

namespace App\Http\Controllers\Total;

use App\Http\Controllers\BaseController;
use App\Services\Total\TotalService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected TotalService $service;

    public function __construct(TotalService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'Total deleted successfully.', Response::HTTP_OK);
    }
}
