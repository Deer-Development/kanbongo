<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\Controller;
use App\Services\Container\ContainerService;
use Illuminate\Http\Request;
use App\Models\Container;
use Illuminate\Http\JsonResponse;

class TransferOwnership extends Controller
{
    public function __invoke(
        Request $request,
        int $id,
        ContainerService $containerService
    ): JsonResponse {
        $request->validate([
            'new_owner_id' => 'required|exists:users,id'
        ]);

        $container = Container::findOrFail($id);
        
        $containerService->transferOwnership($container, $request->new_owner_id);
        
        return response()->json([
            'message' => 'Ownership transferred successfully'
        ]);
    }
} 