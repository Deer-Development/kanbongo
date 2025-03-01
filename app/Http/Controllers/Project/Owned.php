<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\BaseController;
use App\Services\Project\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Project\ProjectResource;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;

class Owned extends BaseController
{
    protected ProjectService $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $isSuperAdmin = Auth::user()->hasRole('Super-Admin');

        if ($isSuperAdmin) {
            $items = Project::all();
        } else {
            $items = Project::where('owner_id', Auth::user()->id)->get();
        }

        return $this->successResponse([
            'items' => ProjectResource::collection($items),
            'totalPages' => null,
            'totalItems' => null,
            'page' => null,
            'isSuperAdmin' => $isSuperAdmin,
        ], 'Project list retrieved successfully.');
    }
}
