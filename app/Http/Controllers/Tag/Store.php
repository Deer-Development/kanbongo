<?php

namespace App\Http\Controllers\Tag;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Tag\ValidateTagStore;
use App\Services\Tag\TagService;
use App\Http\Resources\Tag\TagResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(TagService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateTagStore $request): JsonResponse
    {
        $model = $this->service->create($request->validated());

        return $this->successResponse(new TagResource($model), 'Tag created successfully.', Response::HTTP_CREATED);
    }
}
