<?php

namespace App\Http\Controllers\Tag;

use App\Http\Controllers\BaseController;
use App\Services\Tag\TagService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Destroy extends BaseController
{
    protected TagService $service;

    public function __construct(TagService $service)
    {
        $this->service = $service;
    }

    public function __invoke(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse([], 'Tag deleted successfully.', Response::HTTP_OK);
    }
}
