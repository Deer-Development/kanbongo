<?php

namespace App\Http\Controllers\Board;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Board\ValidateBoardUpdate;
use App\Services\Board\BoardService;
use App\Http\Resources\Board\BoardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StateUpdate extends BaseController
{
    protected $service;

    public function __construct(BoardService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $model = $this->service->updateState($id, $request->all());

        return $this->successResponse(new BoardResource($model), 'Board updated successfully.', Response::HTTP_OK);
    }
}
