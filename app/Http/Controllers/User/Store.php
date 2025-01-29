<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Http\Requests\User\ValidateUserStore;
use App\Notifications\WelcomeEmail;
use App\Services\User\UserService;
use App\Http\Resources\User\UserResource;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Store extends BaseController
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function __invoke(ValidateUserStore $request): JsonResponse
    {
        $data = $request->validated();

//        if (!empty($data['first_name']) || !empty($data['last_name'])) {
//            $data['first_login'] = false;
//        }

        $data['invited_at'] = now();
        $data['invited_by'] = Auth::user()->id;
        $model = $this->service->create($data);

        $model->notify(new WelcomeEmail());

        return $this->successResponse(new UserResource($model), 'User created successfully.', Response::HTTP_CREATED);
    }
}
