<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserToken;
use App\Notifications\RegisterTokenNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        $user = User::create([
            'email' => $request->email,
            'first_login' => true,
            'from_registration' => true,
        ]);

        UserToken::invalidateTokens($user->id);

        $userToken = UserToken::generateForUser($user->id);

        $user->notify(new RegisterTokenNotification($userToken->token));

        return response()->json(['message' => 'Login token sent to your email.']);
    }
}
