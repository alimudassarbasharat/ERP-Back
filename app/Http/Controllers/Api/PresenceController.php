<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\DirectMessageConversation;
use App\Models\UserPresence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PresenceController extends Controller
{
    /**
     * Update user presence status
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:online,away,busy,offline',
            'status_text' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $presence = UserPresence::updateUserStatus(
            $user->id,
            $request->status,
            $request->status_text
        );

        // Broadcast presence update to relevant channels
        $channels = $user->channels()->pluck('id');
        foreach ($channels as $channelId) {
            broadcast(new \App\Events\UserPresenceUpdated($user, $presence, $channelId))->toOthers();
        }

        return response()->json([
            'success' => true,
            'data' => $presence
        ]);
    }

    /**
     * Get online users
     */
    public function getOnlineUsers()
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        // Get users from same channels and conversations
        $channelUserIds = $user->channels()
            ->join('channel_users', 'channels.id', '=', 'channel_users.channel_id')
            ->where('channel_users.user_id', '!=', $user->id)
            ->pluck('channel_users.user_id')
            ->unique();

        $dmUserIds = $user->directMessageConversations()
            ->join('direct_message_participants', 'direct_message_conversations.id', '=', 'direct_message_participants.conversation_id')
            ->where('direct_message_participants.user_id', '!=', $user->id)
            ->pluck('direct_message_participants.user_id')
            ->unique();

        $allUserIds = $channelUserIds->merge($dmUserIds)->unique();

        $onlineUsers = UserPresence::whereIn('user_id', $allUserIds)
            ->where('status', '!=', 'offline')
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->with('user:id,name,avatar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $onlineUsers
        ]);
    }

    /**
     * Send typing indicator
     */
    public function typing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:channel,dm',
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        $cacheKey = "typing.{$request->type}.{$request->id}.{$user->id}";

        // Store typing indicator in cache for 10 seconds
        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar
            ],
            'started_at' => now()
        ], 10);

        // Broadcast typing event
        if ($request->type === 'channel') {
            $channel = Channel::findOrFail($request->id);
            if ($channel->isUserMember($user)) {
                broadcast(new \App\Events\UserTyping($user, 'channel', $channel->id))->toOthers();
            }
        } else {
            $conversation = DirectMessageConversation::findOrFail($request->id);
            if ($conversation->participants()->where('user_id', $user->id)->exists()) {
                broadcast(new \App\Events\UserTyping($user, 'dm', $conversation->id))->toOthers();
            }
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Stop typing indicator
     */
    public function stopTyping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:channel,dm',
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        $cacheKey = "typing.{$request->type}.{$request->id}.{$user->id}";

        // Remove from cache
        Cache::forget($cacheKey);

        // Broadcast stop typing event
        if ($request->type === 'channel') {
            broadcast(new \App\Events\UserStoppedTyping($user, 'channel', $request->id))->toOthers();
        } else {
            broadcast(new \App\Events\UserStoppedTyping($user, 'dm', $request->id))->toOthers();
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Get typing users for a channel or conversation
     */
    public function getTypingUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:channel,dm',
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pattern = "typing.{$request->type}.{$request->id}.*";
        $keys = Cache::get($pattern, []);
        
        $typingUsers = [];
        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data) {
                $typingUsers[] = $data;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $typingUsers
        ]);
    }

    /**
     * Heartbeat - keep user online
     */
    public function heartbeat()
    {
        $user = Auth::user();
        
        UserPresence::updateUserStatus($user->id, 'online');

        return response()->json([
            'success' => true,
            'timestamp' => now()
        ]);
    }
}