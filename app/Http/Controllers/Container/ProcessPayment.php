<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Container\ContainerResource;
use App\Services\Container\ContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProcessPayment extends BaseController
{
    protected $service;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id, int $userId, Request $request): JsonResponse
    {
        $dateRange = $request->input('date_range');
        $selectedEntries = $request->input('selected_entries');
        $model = $this->service->processPayment($id, $userId, $dateRange, $selectedEntries);

        return $this->successResponse(new ContainerResource($model), 'Payment processed successfully.', Response::HTTP_OK);
    }
}
