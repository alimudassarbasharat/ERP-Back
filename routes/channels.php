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

// Global channel for all authenticated users (scoped by merchant_id in events)
Broadcast::channel('global', function ($user) {
    // Note: Global channel is available to all authenticated users
    // Tenant isolation is enforced at the event level (merchant_id in payload)
    return $user ? true : false;
});

// Channel presence channel
Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    $userService = app(\App\Services\UserService::class);
    $userModel = $userService->getUserFromAuth($user);
    
    if (!$userModel) {
        return false;
    }
    
    // CRITICAL FIX: Use withoutTenantScope to find channel, then verify merchant_id
    $channel = \App\Models\Channel::withoutTenantScope()->find($channelId);
    if (!$channel) {
        return false;
    }
    
    // CRITICAL: Verify tenant scoping - channel must belong to same merchant
    if ($channel->merchant_id !== $userModel->merchant_id) {
        return false;
    }
    
    return $channel->isUserMember($userModel);
});

// Direct message channel
Broadcast::channel('dm.{conversationId}', function ($user, $conversationId) {
    $userService = app(\App\Services\UserService::class);
    $userModel = $userService->getUserFromAuth($user);
    
    if (!$userModel) {
        return false;
    }
    
    // CRITICAL FIX: Use withoutTenantScope to find conversation, then verify tenant scoping
    $conversation = \App\Models\DirectMessageConversation::withoutTenantScope()->find($conversationId);
    if (!$conversation) {
        return false;
    }
    
    // CRITICAL: Verify tenant scoping - conversation must belong to same merchant
    if ($conversation->merchant_id !== $userModel->merchant_id) {
        return false;
    }
    
    return $conversation->participants()->where('user_id', $userModel->id)->exists();
});

// Presence channels (return user info)
Broadcast::channel('presence-channel.{channelId}', function ($user, $channelId) {
    $userService = app(\App\Services\UserService::class);
    $userModel = $userService->getUserFromAuth($user);
    
    if (!$userModel) {
        return false;
    }
    
    // CRITICAL FIX: Use withoutTenantScope to find channel, then verify merchant_id
    $channel = \App\Models\Channel::withoutTenantScope()->find($channelId);
    if (!$channel) {
        return false;
    }
    
    // CRITICAL: Verify tenant scoping
    if ($channel->merchant_id !== $userModel->merchant_id) {
        return false;
    }
    
    if (!$channel->isUserMember($userModel)) {
        return false;
    }
    
    return [
        'id' => $userModel->id,
        'name' => $userModel->name,
        'avatar' => $userModel->avatar
    ];
});

Broadcast::channel('presence-dm.{conversationId}', function ($user, $conversationId) {
    $userService = app(\App\Services\UserService::class);
    $userModel = $userService->getUserFromAuth($user);
    
    if (!$userModel) {
        return false;
    }
    
    // CRITICAL FIX: Use withoutTenantScope to find conversation, then verify tenant scoping
    $conversation = \App\Models\DirectMessageConversation::withoutTenantScope()->find($conversationId);
    if (!$conversation) {
        return false;
    }
    
    // CRITICAL: Verify tenant scoping - conversation must belong to same merchant
    if ($conversation->merchant_id !== $userModel->merchant_id) {
        return false;
    }
    
    if (!$conversation->participants()->where('user_id', $userModel->id)->exists()) {
        return false;
    }
    
    return [
        'id' => $userModel->id,
        'name' => $userModel->name,
        'avatar' => $userModel->avatar
    ];
});

// User notification channel (for mentions)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    $userService = app(\App\Services\UserService::class);
    $userModel = $userService->getUserFromAuth($user);
    
    if (!$userModel) {
        return false;
    }
    
    return (int) $userModel->id === (int) $userId;
});
