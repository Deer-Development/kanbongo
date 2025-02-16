<?php

namespace App\Http\Controllers\Tag;

use App\Http\Controllers\BaseController;
use App\Services\Tag\TagService;
use App\Http\Resources\Tag\TagResource;
use Illuminate\Http\JsonResponse;

class Show extends BaseController
{
    protected $service;

    public function __construct(TagService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $model = $this->service->getById($id);

        return $this->successResponse(new TagResource($model), 'Tag details fetched successfully.');
    }
}
