<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Http\Requests\User\UpdateNotificationPreferencesRequest;
use App\Services\User\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;

class UpdateNotificationPreferences extends BaseController
{
    public function __construct(
        private readonly NotificationPreferenceService $service
    ) {}

    public function __invoke(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $preferences = $this->service->updatePreferences(
            $request->user(),
            $request->validated()
        );

        return $this->successResponse($preferences, 'Notification preferences updated successfully');
    }
} 