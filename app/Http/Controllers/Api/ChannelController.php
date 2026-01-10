<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Channel;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChannelController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * List all channels for the authenticated user
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        // CRITICAL FIX: Ensure tenant scoping - only show channels for user's merchant_id
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        // CRITICAL FIX: Use direct query through channel_users to ensure membership visibility
        // This fixes the bug where members don't see channels they belong to
        // Also include unread_count from pivot table
        $channels = Channel::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('channels.merchant_id', $merchantId)
            ->where('channels.is_archived', false)
            ->with(['creator:id,name,avatar', 'latestMessage.user:id,name,avatar'])
            ->withCount('users')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($channel) use ($user) {
                // CRITICAL: Get unread_count from pivot table
                $pivot = $channel->users()->where('user_id', $user->id)->first();
                $channel->unread_count = $pivot ? ($pivot->pivot->unread_count ?? 0) : 0;
                $channel->is_muted = $pivot ? ($pivot->pivot->is_muted ?? false) : false;
                return $channel;
            });

        // CRITICAL FIX: Public channels must also be tenant-scoped
        $publicChannels = Channel::where('type', 'public')
            ->where('merchant_id', $merchantId) // Tenant scope
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
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }

        // CRITICAL FIX: Ensure merchant_id is set when creating channel
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $channel = Channel::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type ?? 'public',
            'created_by' => $user->id,
            'merchant_id' => $merchantId // Explicitly set merchant_id
        ]);

        $channel->addMember($user, 'admin');

        if ($request->has('initial_members')) {
            foreach ($request->initial_members as $memberId) {
                if ($memberId != $user->id) {
                    $memberToAdd = User::where('id', $memberId)
                        ->where('merchant_id', $merchantId) // Tenant scope
                        ->first();
                    
                    if ($memberToAdd) {
                        $channel->addMember($memberToAdd, 'member');
                        
                        // FEATURE: Create system message
                        $systemMessage = \App\Models\Message::create([
                            'channel_id' => $channel->id,
                            'user_id' => $user->id,
                            'content' => "{$user->name} added {$memberToAdd->name} to this channel",
                            'type' => 'system',
                            'metadata' => [
                                'action' => 'user_added',
                                'adder_id' => $user->id,
                                'adder_name' => $user->name,
                                'added_user_id' => $memberToAdd->id,
                                'added_user_name' => $memberToAdd->name
                            ],
                            'merchant_id' => $merchantId
                        ]);
                        
                        // Broadcast system message
                        broadcast(new \App\Events\MessageSent($systemMessage, $channel))->toOthers();
                        
                        // CRITICAL: Broadcast so new member sees channel in sidebar immediately
                        broadcast(new \App\Events\UserJoinedChannel($channel, $memberToAdd))->toOthers();
                    }
                }
            }
        }

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
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        // CRITICAL FIX: Use withoutTenantScope to find channel, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $channel = Channel::withoutTenantScope()->find($id);
        
        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this channel'
            ], 403);
        }

        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        $channel->markAsRead($user);

        $messages = $channel->messages()
            ->with([
                'user:id,name,avatar',
                'reactions',
                'attachments',
                'replies' => function ($query) {
                    $query->latest()->limit(3);
                }
            ])
            ->whereNull('parent_id')
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
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
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

        // CRITICAL FIX: Verify user and channel belong to same merchant
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot join channels from other organizations'
            ], 403);
        }
        
        $channel->addMember($user);

        // FEATURE: Create system message when user joins
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $systemMessage = \App\Models\Message::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'content' => "{$user->name} joined this channel",
            'type' => 'system',
            'metadata' => [
                'action' => 'user_joined',
                'user_id' => $user->id,
                'user_name' => $user->name
            ],
            'merchant_id' => $merchantId // CRITICAL: Set merchant_id
        ]);
        
        // Broadcast system message
        broadcast(new \App\Events\MessageSent($systemMessage, $channel))->toOthers();
        
        // CRITICAL: Broadcast UserJoinedChannel so sidebar updates for all members
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
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        $channel = Channel::findOrFail($id);

        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Not a member of this channel'
            ], 400);
        }

        $channel->removeMember($user);

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
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        $channel = Channel::findOrFail($id);

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
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        $channel = Channel::findOrFail($id);

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

        // CRITICAL FIX: Ensure all users being added belong to same merchant_id
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        // Verify channel belongs to same merchant
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot add members to channels from other organizations'
            ], 403);
        }
        
        $addedUsers = [];
        $addedUserModels = [];
        
        foreach ($request->user_ids as $userId) {
            $userToAdd = User::where('id', $userId)
                ->where('merchant_id', $merchantId) // Tenant scope
                ->first();
                
            if ($userToAdd && !$channel->isUserMember($userToAdd)) {
                // CRITICAL: Verify user belongs to same merchant as channel
                if ($userToAdd->merchant_id === $channel->merchant_id) {
                    $channel->addMember($userToAdd);
                    $addedUsers[] = $userId;
                    $addedUserModels[] = $userToAdd;
                }
            }
        }

        $currentMemberCount = $channel->users()->count();
        $maxMembers = config('messaging.max_channel_members', 1000);
        
        if ($currentMemberCount + count($request->user_ids) > $maxMembers) {
            return response()->json([
                'success' => false,
                'message' => "Channel member limit ($maxMembers) would be exceeded"
            ], 422);
        }

        // FEATURE: Create system messages for each added user
        // CRITICAL: System message content is personalized per viewer on frontend
        // Backend stores generic message, frontend shows "You" for the actor
        foreach ($addedUserModels as $addedUser) {
            $systemMessage = \App\Models\Message::create([
                'channel_id' => $channel->id,
                'user_id' => $user->id, // The user who added them
                'content' => "{$user->name} added {$addedUser->name} to this channel",
                'type' => 'system',
                'metadata' => [
                    'action' => 'user_added',
                    'adder_id' => $user->id,
                    'adder_name' => $user->name,
                    'added_user_id' => $addedUser->id,
                    'added_user_name' => $addedUser->name
                ],
                'merchant_id' => $merchantId // CRITICAL: Set merchant_id
            ]);

            // Broadcast system message to all channel members
            broadcast(new \App\Events\MessageSent($systemMessage, $channel))->toOthers();
            
            // CRITICAL: Broadcast ChannelUpdated so new member sees channel in sidebar immediately
            broadcast(new \App\Events\UserJoinedChannel($channel, $addedUser))->toOthers();
        }

        // Broadcast channel updated event (so sidebar updates)
        broadcast(new \App\Events\ChannelUpdated($channel))->toOthers();

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
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
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
     * Get channel messages (FIX: 404 error)
     */
    public function getMessages($id, Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        // CRITICAL FIX: Use withoutTenantScope to find channel, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $channel = Channel::withoutTenantScope()->find($id);
        
        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this channel'
            ], 403);
        }
        
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this channel'
            ], 403);
        }

        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        // Mark as read when fetching messages
        $channel->markAsRead($user);

        $messages = $channel->messages()
            ->with([
                'user:id,name,avatar',
                'reactions',
                'attachments',
                'replies' => function ($query) {
                    $query->latest()->limit(3);
                }
            ])
            ->whereNull('parent_id')
            ->latest()
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Mute a channel
     */
    public function mute($id)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        // CRITICAL FIX: Use withoutTenantScope to find channel, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $channel = Channel::withoutTenantScope()->find($id);
        
        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this channel'
            ], 403);
        }
        
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this channel'
            ], 403);
        }

        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        $channel->users()->updateExistingPivot($user->id, [
            'is_muted' => true
        ]);

        // Broadcast mute event
        broadcast(new \App\Events\ChannelMuted($channel, $user))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Channel muted'
        ]);
    }

    /**
     * Unmute a channel
     */
    public function unmute($id)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        // CRITICAL FIX: Use withoutTenantScope to find channel, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $channel = Channel::withoutTenantScope()->find($id);
        
        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this channel'
            ], 403);
        }
        
        if ($channel->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this channel'
            ], 403);
        }

        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        $channel->users()->updateExistingPivot($user->id, [
            'is_muted' => false
        ]);

        // Broadcast unmute event
        broadcast(new \App\Events\ChannelUnmuted($channel, $user))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Channel unmuted'
        ]);
    }

    /**
     * Search channels
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
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