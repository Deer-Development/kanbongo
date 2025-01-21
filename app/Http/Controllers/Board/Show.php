<?php

namespace App\Http\Controllers\Board;

use App\Http\Controllers\BaseController;
use App\Services\Board\BoardService;
use App\Http\Resources\Board\BoardResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(BoardService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new BoardResource($model), 'Board details fetched successfully.');
    }
}
