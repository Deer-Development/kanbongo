<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UserToken extends Model
{
    protected $fillable = ['user_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new token for the user.
     */
    public static function generateForUser($userId): self
    {
        $token = strtoupper(Str::random(6));

        return self::create([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Validate the given token.
     */
    public static function validateToken($userId, $token): bool
    {
        $userToken = self::where('user_id', $userId)
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        return $userToken !== null;
    }

    /**
     * Delete all tokens for the user.
     */
    public static function invalidateTokens($userId): void
    {
        self::where('user_id', $userId)->delete();
    }
}
