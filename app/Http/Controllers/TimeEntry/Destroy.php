<?php

namespace App\Http\Controllers\TimeEntry;

use App\Http\Controllers\BaseController;
use App\Services\TimeEntry\TimeEntryService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected TimeEntryService $service;

    public function __construct(TimeEntryService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'TimeEntry deleted successfully.', Response::HTTP_OK);
    }
}
