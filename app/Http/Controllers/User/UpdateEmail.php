<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\UserToken;
use App\Notifications\EmailChangeTokenNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UpdateEmail extends BaseController
{
    public function sendToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email,' . $request->user()->id],
        ]);

        $user = $request->user();
        
        // Invalidate any existing tokens
        UserToken::invalidateTokens($user->id);

        // Generate new token
        $userToken = UserToken::generateForUser($user->id);

        // Send notification with token
        $user->notify(new EmailChangeTokenNotification($userToken->token, $request->email));

        return $this->successResponse(null, 'Email change token sent successfully.');
    }

    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'token' => 'required|string',
        ]);

        $user = $request->user();

        if (!UserToken::validateToken($user->id, $request->token)) {
            throw ValidationException::withMessages([
                'token' => 'The provided token is invalid or has expired.',
            ]);
        }

        // Invalidate used token
        UserToken::invalidateTokens($user->id);

        // Update email
        $user->update(['email' => $request->email]);

        return $this->successResponse(null, 'Email updated successfully.');
    }
} 
