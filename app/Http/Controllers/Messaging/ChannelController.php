<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChannelController extends Controller
{
    /**
     * Get all channels for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $channels = $user->channels()
            ->with(['latestMessage.user', 'members.user'])
            ->withCount(['messages', 'members'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Add unread count for each channel
        $channels->each(function ($channel) use ($user) {
            $channel->unread_count = $channel->unreadCountForUser($user->id);
        });

        return response()->json([
            'success' => true,
            'data' => $channels
        ]);
    }

    /**
     * Get a specific channel with messages
     */
    public function show(Request $request, $channelId)
    {
        $user = Auth::user();
        
        $channel = Channel::with(['members.user', 'messages.user', 'messages.reactions', 'messages.attachments'])
            ->findOrFail($channelId);

        // Check if user is a member
        if (!$channel->isMember($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        // Mark as read
        $channel->members()->where('user_id', $user->id)->update([
            'last_read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $channel
        ]);
    }

    /**
     * Create a new channel
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:public,private,direct',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        $channel = Channel::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'icon' => $request->icon,
            'color' => $request->color ?? '#4A154B',
            'is_active' => true
        ]);

        // Add creator as admin
        $channel->members()->create([
            'user_id' => $user->id,
            'role' => 'admin',
            'joined_at' => now()
        ]);

        // Add other members
        foreach ($request->member_ids as $memberId) {
            if ($memberId != $user->id) {
                $channel->members()->create([
                    'user_id' => $memberId,
                    'role' => 'member',
                    'joined_at' => now()
                ]);
            }
        }

        $channel->load(['members.user', 'latestMessage.user']);

        return response()->json([
            'success' => true,
            'message' => 'Channel created successfully',
            'data' => $channel
        ], 201);
    }

    /**
     * Update channel
     */
    public function update(Request $request, $channelId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $channel = Channel::findOrFail($channelId);

        // Check if user is admin
        $membership = $channel->members()->where('user_id', $user->id)->first();
        if (!$membership || $membership->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only channel admins can update the channel'
            ], 403);
        }

        $channel->update($request->only(['name', 'description', 'icon', 'color', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Channel updated successfully',
            'data' => $channel
        ]);
    }

    /**
     * Delete channel
     */
    public function destroy($channelId)
    {
        $user = Auth::user();
        $channel = Channel::findOrFail($channelId);

        // Check if user is admin
        $membership = $channel->members()->where('user_id', $user->id)->first();
        if (!$membership || $membership->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only channel admins can delete the channel'
            ], 403);
        }

        $channel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Channel deleted successfully'
        ]);
    }

    /**
     * Add member to channel
     */
    public function addMember(Request $request, $channelId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:admin,moderator,member'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $channel = Channel::findOrFail($channelId);

        // Check if user is admin or moderator
        $membership = $channel->members()->where('user_id', $user->id)->first();
        if (!$membership || !in_array($membership->role, ['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins and moderators can add members'
            ], 403);
        }

        // Check if user is already a member
        if ($channel->isMember($request->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'User is already a member of this channel'
            ], 400);
        }

        $channel->members()->create([
            'user_id' => $request->user_id,
            'role' => $request->role ?? 'member',
            'joined_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully'
        ]);
    }

    /**
     * Remove member from channel
     */
    public function removeMember(Request $request, $channelId, $memberId)
    {
        $user = Auth::user();
        $channel = Channel::findOrFail($channelId);

        // Check if user is admin or moderator
        $membership = $channel->members()->where('user_id', $user->id)->first();
        if (!$membership || !in_array($membership->role, ['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins and moderators can remove members'
            ], 403);
        }

        // Cannot remove yourself as admin
        if ($memberId == $user->id && $membership->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove yourself as admin'
            ], 400);
        }

        $channel->members()->where('user_id', $memberId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully'
        ]);
    }

    /**
     * Leave channel
     */
    public function leave(Request $request, $channelId)
    {
        $user = Auth::user();
        $channel = Channel::findOrFail($channelId);

        $membership = $channel->members()->where('user_id', $user->id)->first();
        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 400);
        }

        $membership->delete();

        return response()->json([
            'success' => true,
            'message' => 'Left channel successfully'
        ]);
    }

    /**
     * Get available users for adding to channel
     */
    public function getAvailableUsers(Request $request, $channelId)
    {
        $channel = Channel::findOrFail($channelId);
        
        $existingMemberIds = $channel->members()->pluck('user_id');
        
        $users = User::whereNotIn('id', $existingMemberIds)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
} 