<?php

use Illuminate\Support\Facades\Broadcast;


// Only members of a chat may subscribe to its private channel. Leaving this
// open (return true) let ANY authenticated user listen to ANY chat's messages.
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return \App\Models\Chat::where('id', $chatId)
        ->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->exists();
});