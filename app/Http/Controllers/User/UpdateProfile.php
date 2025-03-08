<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Services\User\UserService;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;

class UpdateProfile extends BaseController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );

        $user->isSuperAdmin = $user->hasRole('Super-Admin');

        return $this->successResponse(
            $user,
            'Profile updated successfully'
        );
    }
} 