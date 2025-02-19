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
            'email' => 'required|email',
        ]);

        // Check if the user already exists
        $user = User::where('email', $request->email)->first();

        if ($user) {
            if ($user->is_temporary) {
                // If the user is temporary, update the record to make it a regular user
                $user->update([
                    'is_temporary' => false,
                    'first_login' => true,
                    'from_registration' => true,
                ]);
            } else {
                // If the user already exists and is not temporary, return an error
                throw ValidationException::withMessages([
                    'email' => ['This email is already registered.'],
                ]);
            }
        } else {
            // If the user does not exist, create a new record
            $user = User::create([
                'email' => $request->email,
                'first_login' => true,
                'from_registration' => true,
            ]);
        }

        // Invalidate any existing login tokens for this user
        UserToken::invalidateTokens($user->id);

        // Generate a new login token
        $userToken = UserToken::generateForUser($user->id);

        // Send the login token notification to the user
        $user->notify(new RegisterTokenNotification($userToken->token));

        return response()->json(['message' => 'Login token sent to your email.']);
    }
}
