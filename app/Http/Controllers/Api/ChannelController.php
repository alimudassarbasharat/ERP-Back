<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChannelController extends Controller
{
    /**
     * List all channels for the authenticated user
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        
        // Get channels the user is a member of
        $channels = $user->channels()
            ->with(['creator:id,name,avatar', 'latestMessage.user:id,name,avatar'])
            ->withCount('users')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Get public channels user can join
        $publicChannels = Channel::where('type', 'public')
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['creator:id,name,avatar'])
            ->withCount('users')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'joined_channels' => $channels,
                'available_channels' => $publicChannels
            ]
        ]);
    }

    /**
     * Create a new channel
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:channels',
            'description' => 'nullable|string|max:500',
            'type' => 'in:public,private',
            'initial_members' => 'array',
            'initial_members.*' => 'exists:users,id'
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
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $channel = Channel::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type ?? 'public',
            'created_by' => $user->id
        ]);

        // Add creator as admin
        $channel->addMember($user, 'admin');

        // Add initial members
        if ($request->has('initial_members')) {
            foreach ($request->initial_members as $memberId) {
                if ($memberId != $user->id) {
                    $channel->addMember(User::find($memberId), 'member');
                }
            }
        }

        // Broadcast channel created event
        broadcast(new \App\Events\ChannelCreated($channel))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $channel->load(['users', 'creator'])
        ], 201);
    }

    /**
     * Get channel details with messages
     */
    public function show($id, Request $request)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $channel = Channel::findOrFail($id);

        // Check if user is member
        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        // Mark channel as read
        $channel->markAsRead($user);

        // Get messages with pagination
        $messages = $channel->messages()
            ->with([
                'user:id,name,avatar',
                'reactions',
                'attachments',
                'replies' => function ($query) {
                    $query->latest()->limit(3);
                }
            ])
            ->whereNull('parent_id') // Only get parent messages
            ->latest()
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => [
                'channel' => $channel->load(['users.presence', 'creator']),
                'messages' => $messages
            ]
        ]);
    }

    /**
     * Join a channel
     */
    public function join($id)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $channel = Channel::findOrFail($id);

        if ($channel->type === 'private') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot join private channel without invitation'
            ], 403);
        }

        if ($channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Already a member of this channel'
            ], 400);
        }

        $channel->addMember($user);

        // Broadcast user joined event
        broadcast(new \App\Events\UserJoinedChannel($channel, $user))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined channel',
            'data' => $channel->load('users')
        ]);
    }

    /**
     * Leave a channel
     */
    public function leave($id)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $channel = Channel::findOrFail($id);

        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Not a member of this channel'
            ], 400);
        }

        $channel->removeMember($user);

        // Broadcast user left event
        broadcast(new \App\Events\UserLeftChannel($channel, $user))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Successfully left channel'
        ]);
    }

    /**
     * Update channel settings
     */
    public function update($id, Request $request)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $channel = Channel::findOrFail($id);

        // Check if user is admin
        $member = $channel->users()->where('user_id', $user->id)->first();
        if (!$member || $member->pivot->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only channel admins can update settings'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:channels,name,' . $id,
            'description' => 'nullable|string|max:500',
            'type' => 'in:public,private'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $channel->update($request->only(['name', 'description', 'type']));

        // Broadcast channel updated event
        broadcast(new \App\Events\ChannelUpdated($channel))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $channel
        ]);
    }

    /**
     * Add members to channel
     */
    public function addMembers($id, Request $request)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $channel = Channel::findOrFail($id);

        // Check permissions
        if ($channel->type === 'private') {
            $member = $channel->users()->where('user_id', $user->id)->first();
            if (!$member || $member->pivot->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can add members to private channels'
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $addedUsers = [];
        foreach ($request->user_ids as $userId) {
            if (!$channel->isUserMember(User::find($userId))) {
                $channel->addMember(User::find($userId));
                $addedUsers[] = $userId;
            }
        }

        // Check channel member limit
        $currentMemberCount = $channel->users()->count();
        $maxMembers = config('messaging.max_channel_members', 1000);
        
        if ($currentMemberCount + count($request->user_ids) > $maxMembers) {
            return response()->json([
                'success' => false,
                'message' => "Channel member limit ($maxMembers) would be exceeded"
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($addedUsers) . ' members added',
            'data' => $channel->load('users')
        ]);
    }

    /**
     * Delete a channel
     */
    public function destroy($id)
    {
        $channel = Channel::findOrFail($id);
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        
        // Only channel creator or admin can delete
        $member = $channel->users()->where('user_id', $user->id)->first();
        if ($channel->created_by !== $user->id && (!$member || $member->pivot->role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this channel'
            ], 403);
        }
        
        // Don't allow deletion of the general channel
        if ($channel->slug === 'general') {
            return response()->json([
                'success' => false,
                'message' => 'The general channel cannot be deleted'
            ], 403);
        }
        
        // Soft delete the channel
        $channel->is_archived = true;
        $channel->save();
        
        // Delete all messages in the channel
        $channel->messages()->delete();
        
        // Remove all members
        $channel->users()->detach();
        
        return response()->json([
            'success' => true,
            'message' => 'Channel deleted successfully'
        ]);
    }

    /**
     * Search channels
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
        
        $channels = Channel::where('is_archived', false)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->when(!$request->get('include_private'), function($q) {
                $q->where('type', 'public');
            })
            ->with(['creator:id,name,avatar'])
            ->withCount('users')
            ->take(20)
            ->get();
        
        // Filter private channels to only show those the user is member of
        if ($request->get('include_private')) {
            $channels = $channels->filter(function($channel) use ($user) {
                return $channel->type !== 'private' || $channel->isUserMember($user);
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => $channels->values()
        ]);
    }
}