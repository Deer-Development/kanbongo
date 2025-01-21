<?php

namespace App\Http\Controllers\Board;

use App\Http\Controllers\BaseController;
use App\Services\Board\BoardService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected BoardService $service;

    public function __construct(BoardService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'Board deleted successfully.', Response::HTTP_OK);
    }
}
