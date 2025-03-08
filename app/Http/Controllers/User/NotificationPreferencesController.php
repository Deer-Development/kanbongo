<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Services\User\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;

class NotificationPreferencesController extends BaseController
{
    public function __construct(
        private readonly NotificationPreferenceService $service
    ) {}

    public function index(): JsonResponse
    {
        $preferences = $this->service->getPreferences(auth()->user());

        return $this->successResponse($preferences);
    }
}
