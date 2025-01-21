<?php

namespace App\Http\Controllers\Board;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Board\ValidateBoardStore;
use App\Services\Board\BoardService;
use App\Http\Resources\Board\BoardResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(BoardService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateBoardStore $request): JsonResponse
    {
        $model = $this->service->create($request->validated());

        return $this->successResponse(new BoardResource($model), 'Board created successfully.', Response::HTTP_CREATED);
    }
}
