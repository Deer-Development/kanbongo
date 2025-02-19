<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserToken;
use App\Notifications\LoginTokenNotification;
use App\Rules\EmailExistsAndNotTemporary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function sendLoginToken(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', new EmailExistsAndNotTemporary()],
        ]);

        $user = User::where('email', $request->email)->first();

        UserToken::invalidateTokens($user->id);

        $userToken = UserToken::generateForUser($user->id);

        $user->notify(new LoginTokenNotification($userToken->token));

        return response()->json(['message' => 'Login token sent to your email.']);
    }
// test23@gmail.com
    public function verifyLoginToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!UserToken::validateToken($user->id, $request->token)) {
            throw ValidationException::withMessages([
                'token' => 'The provided token is invalid or has expired.',
            ]);
        }

        UserToken::invalidateTokens($user->id);

        Auth::login($user);
        $authToken = $user->createToken('API Token')->plainTextToken;
        $user->isSuperAdmin = $user->hasRole('Super-Admin');

        return response()->json([
            'user' => $user,
            'token' => $authToken,
        ]);
    }

    public function updateDetails(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'first_login' => 'required|boolean',
            'timezone' => 'required|string',
        ]);

        $user = Auth::user();
        $user->update($request->only(['first_name', 'last_name', 'first_login', 'timezone']));
        $user->isSuperAdmin = $user->hasRole('Super-Admin');
        return response()->json(['user' => $user]);
    }
}
