<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Global channel for all authenticated users
Broadcast::channel('global', function ($user) {
    return $user ? true : false;
});

// Channel presence channel
Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    $channel = \App\Models\Channel::find($channelId);
    if (!$channel) {
        return false;
    }
    
    return $channel->isUserMember($user);
});

// Direct message presence channel
Broadcast::channel('dm.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\DirectMessageConversation::find($conversationId);
    if (!$conversation) {
        return false;
    }
    
    return $conversation->participants()->where('user_id', $user->id)->exists();
});

// Presence channels (return user info)
Broadcast::channel('presence-channel.{channelId}', function ($user, $channelId) {
    $channel = \App\Models\Channel::find($channelId);
    if (!$channel || !$channel->isUserMember($user)) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar
    ];
});

Broadcast::channel('presence-dm.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\DirectMessageConversation::find($conversationId);
    if (!$conversation || !$conversation->participants()->where('user_id', $user->id)->exists()) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar
    ];
});
