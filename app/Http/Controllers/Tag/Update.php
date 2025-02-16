<?php

namespace App\Http\Controllers\Tag;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Tag\ValidateTagUpdate;
use App\Services\Tag\TagService;
use App\Http\Resources\Tag\TagResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Update extends BaseController
{
    protected $service;

    public function __construct(TagService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTagUpdate $request, int $id): JsonResponse
    {
        $model = $this->service->update($id, $request->validated());

        return $this->successResponse(new TagResource($model), 'Tag updated successfully.', Response::HTTP_OK);
    }
}
