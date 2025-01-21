<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends Controller
{
    protected function successResponse($data, string $message = 'Request successful', int $status = Response::HTTP_OK): JsonResponse
    {
        if (!is_array(value: $data)) {
            $data = $data instanceof \JsonSerializable ? $data->jsonSerialize() : (array) $data;
        }

        return response()->json(data: [
            'message' => $message,
            'data' => $data,
        ], status: $status);
    }

    protected function errorResponse(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json(data: [
            'message' => $message,
        ], status: $status);
    }
}
