<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use Illuminate\Support\Facades\Log;
Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('notifications.{userId}', function (User $user, $userId) {
    Log::info('Channel authorization attempt', [
        'user_id' => $user->id,
        'requested_channel' => $userId
    ]);
    
    return (int) $user->id === (int) $userId;
}); 