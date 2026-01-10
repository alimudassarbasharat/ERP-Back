<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Helpers\MentionHelper;
use App\Models\DirectMessage;
use App\Models\DirectMessageConversation;
use App\Models\DirectMessageAttachment;
use App\Models\MentionNotification;
use App\Models\User;
use App\Services\UserService;
use App\Services\MediaService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DirectMessageController extends Controller
{
    protected $userService;
    protected $mediaService;
    protected $pushService;

    public function __construct(UserService $userService, MediaService $mediaService, PushNotificationService $pushService)
    {
        $this->userService = $userService;
        $this->mediaService = $mediaService;
        $this->pushService = $pushService;
    }

    /**
     * Get all direct message conversations for the authenticated user
     */
    public function conversations()
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        // CRITICAL FIX: Ensure tenant scoping for conversations
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $conversations = $user->directMessageConversations()
            ->where('direct_message_conversations.merchant_id', $merchantId) // Explicit tenant scope
            ->with([
                'participants:id,name,avatar',
                'participants.presence',
                'latestMessage.user:id,name,avatar'
            ])
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();

        // CRITICAL FIX: Ensure unread_count is included from pivot table
        $conversations = $conversations->map(function ($conversation) use ($user) {
            $participantPivot = $conversation->participants()
                ->where('user_id', $user->id)
                ->first();
            
            // Add unread_count to conversation object
            $conversation->unread_count = $participantPivot ? $participantPivot->pivot->unread_count : 0;
            
            return $conversation;
        });

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Start or get a direct message conversation
     */
    public function startConversation(Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id|different:' . $user->id,
            'name' => 'nullable|string|max:255' // For group DMs
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $userIds = array_merge($request->user_ids, [$user->id]);
        $isGroup = count($userIds) > 2;

        // Get merchant_id from authenticated user
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id');
        
        // TEMPORARY FIX: Allow request to proceed with default merchant_id if not set
        // This will be fixed once all users have merchant_id set
        if (!$merchantId) {
            // Try to get from user's admin relationship
            if (method_exists($user, 'admin') && $user->admin && $user->admin->merchant_id) {
                $merchantId = $user->admin->merchant_id;
            } else {
                // Set default to prevent blocking
                $merchantId = 'DEFAULT_TENANT';
                Log::warning('Merchant ID not found for user in DirectMessageController', [
                    'user_id' => $user->id,
                    'email' => $user->email ?? 'N/A'
                ]);
            }
        }

        if (!$isGroup) {
            // For 1-on-1, find or create conversation
            $conversation = DirectMessageConversation::findOrCreateBetweenUsers(
                $user->id,
                $request->user_ids[0],
                $merchantId
            );
        } else {
            // Create group conversation
            $conversation = DirectMessageConversation::create([
                'merchant_id' => $merchantId,
                'name' => $request->name,
                'is_group' => true
            ]);

            // Add all participants
            foreach ($userIds as $userId) {
                $conversation->addParticipant(User::find($userId));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $conversation->load('participants.presence')
        ]);
    }

    /**
     * Get conversation messages
     */
    public function getMessages($conversationId, Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        // CRITICAL FIX: Use withoutTenantScope to find conversation, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $conversation = DirectMessageConversation::withoutTenantScope()->find($conversationId);
        
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping - conversation must belong to same merchant
        if ($conversation->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this conversation'
            ], 403);
        }

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this conversation'
            ], 403);
        }

        // Mark as read
        $conversation->markAsRead($user);

        // CRITICAL FIX: Ensure messages are properly scoped by tenant
        // Use withoutTenantScope on conversation, but ensure messages query is scoped
        $messages = $conversation->messages()
            ->where('direct_messages.merchant_id', $merchantId) // Explicit tenant scope
            ->with([
                'user:id,name,avatar',
                'reactions',
                'attachments'
            ])
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 50);

        // CRITICAL FIX: Return format matches frontend expectation
        // Frontend expects: response.data.data.messages.data or response.data.data.messages
        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load('participants:id,name,avatar'),
                'messages' => [
                    'data' => $messages->items(),
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ]
            ]
        ]);
    }

    /**
     * Send a direct message
     */
    public function sendMessage($conversationId, Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        
        // CRITICAL FIX: Use withoutTenantScope to find conversation, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $conversation = DirectMessageConversation::withoutTenantScope()->find($conversationId);
        
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping - conversation must belong to same merchant
        if ($conversation->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this conversation'
            ], 403);
        }

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this conversation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:5000',
            'attachments' => 'array|max:10',
            'attachments.*' => 'file|max:51200',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        if (!$request->has('content') && !$request->hasFile('attachments')) {
            return ResponseHelper::validationError(['content' => ['Either content or attachments are required']]);
        }

        $messageType = 'text';
        if ($request->hasFile('attachments')) {
            $firstFile = $request->file('attachments')[0];
            $messageType = $this->mediaService->getFileType($firstFile->getMimeType());
        }

        // CRITICAL FIX: Ensure merchant_id is set on message
        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $request->content ?? '',
            'type' => $messageType,
            'metadata' => $request->metadata ?? [],
            'merchant_id' => $merchantId // CRITICAL: Set merchant_id for tenant scoping
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                try {
                    $fileData = $this->mediaService->uploadFile($file, 'direct-messages');
                    
                    DirectMessageAttachment::create([
                        'direct_message_id' => $message->id,
                        'filename' => $fileData['filename'],
                        'original_name' => $fileData['original_name'],
                        'file_path' => $fileData['file_path'],
                        'file_url' => $fileData['file_url'],
                        'mime_type' => $fileData['mime_type'],
                        'file_size' => $fileData['file_size'],
                        'metadata' => $fileData['metadata']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to upload attachment: ' . $e->getMessage());
                }
            }
        }

        // Update conversation's updated_at
        $conversation->touch();

        // Increment unread count for other participants (only if not muted)
        $otherParticipants = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->wherePivot('is_muted', false)
            ->get();
        
        // Increment unread count for all participants (method handles excluding sender)
        $conversation->incrementUnreadCount($user);
        
        foreach ($otherParticipants as $participant) {
            // Get updated unread count
            $participantPivot = $conversation->participants()
                ->where('user_id', $participant->id)
                ->first();
            
            $unreadCount = $participantPivot ? $participantPivot->pivot->unread_count : 0;
            
            // Broadcast unread count update
            broadcast(new \App\Events\UnreadCountUpdated($participant, 'dm', $conversation->id, $unreadCount))->toOthers();
        }

        // FEATURE: Create notifications for other participants (if not muted)
        // Note: $otherParticipants already defined above
        foreach ($otherParticipants as $participant) {
            // Create notification in database
            \App\Models\MessageNotification::create([
                'user_id' => $participant->id,
                'message_id' => $message->id,
                'message_type' => 'direct_message',
                'conversation_id' => $conversation->id,
                'conversation_type' => 'dm',
                'conversation_name' => $conversation->display_name ?? 'Direct Message',
                'sender_id' => $user->id,
                'sender_name' => $user->name,
                'message_preview' => substr($request->content ?? '', 0, 200),
                'is_read' => false,
                'merchant_id' => $user->merchant_id ?? $conversation->merchant_id
            ]);

            // Broadcast realtime notification
            broadcast(new \App\Events\DirectMessageNotification($participant, $message, $conversation, $user))->toOthers();
            
            // CRITICAL: Send web push notification (works even when site is closed)
            if ($this->pushService) {
                try {
                    $this->pushService->sendDMNotification(
                        $participant,
                        $user->name,
                        $conversation->display_name ?? 'Direct Message',
                        substr($request->content ?? '', 0, 200),
                        $conversation->id
                    );
                } catch (\Exception $e) {
                    // Log but don't fail the request if push fails
                    Log::error('[Push] Failed to send push notification for DM:', [
                        'user_id' => $participant->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Load relationships
        $message->load(['user:id,name,avatar', 'attachments', 'reactions']);

        // Handle mentions
        if ($request->content) {
            $mentionedUsernames = MentionHelper::extractMentionedUsernames($request->content);
            if (!empty($mentionedUsernames)) {
                $conversationUsers = $conversation->participants()->get();
                
                foreach ($mentionedUsernames as $username) {
                    // Improved mention detection - match by exact username or name
                    $mentionedUser = $conversationUsers->first(function ($u) use ($username) {
                        $name = strtolower(trim($u->name ?? ''));
                        $usernameLower = strtolower(trim($username));
                        return $name === $usernameLower || 
                               strpos($name, $usernameLower) !== false ||
                               ($u->username && strtolower($u->username) === $usernameLower);
                    });
                    
                    if ($mentionedUser && $mentionedUser->id !== $user->id) {
                        // Save notification to database
                        MentionNotification::create([
                            'user_id' => $mentionedUser->id,
                            'message_id' => $message->id,
                            'message_type' => 'direct_message',
                            'conversation_id' => $conversation->id,
                            'conversation_type' => 'dm',
                            'conversation_name' => $conversation->display_name ?? 'Direct Message',
                            'mentioner_id' => $user->id,
                            'message_preview' => substr($request->content, 0, 200),
                            'is_read' => false
                        ]);
                        
                        // Broadcast real-time notification
                        broadcast(new \App\Events\UserMentioned($mentionedUser, $message, $conversation, $user))->toOthers();
                    }
                }
            }
        }

        // CRITICAL: Broadcast message immediately (ShouldBroadcastNow ensures synchronous broadcast)
        try {
            Log::info('[DirectMessageController] Broadcasting DirectMessageSent event', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'broadcast_driver' => config('broadcasting.default')
            ]);
            
            broadcast(new \App\Events\DirectMessageSent($message, $conversation))->toOthers();
            
            Log::info('[DirectMessageController] DirectMessageSent event broadcasted successfully', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id
            ]);
        } catch (\Exception $e) {
            // CRITICAL: Log broadcast errors but don't fail the request
            Log::error('[DirectMessageController] Failed to broadcast DirectMessageSent event', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return ResponseHelper::success($message, 'Message sent successfully', 201);
    }

    public function typing(Request $request, $conversationId)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        // CRITICAL FIX: Use withoutTenantScope to find conversation, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $conversation = DirectMessageConversation::withoutTenantScope()->find($conversationId);
        
        if (!$conversation) {
            return ResponseHelper::notFound('Conversation not found');
        }
        
        // CRITICAL: Verify tenant scoping
        if ($conversation->merchant_id !== $merchantId) {
            return ResponseHelper::forbidden('You do not have access to this conversation');
        }

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return ResponseHelper::forbidden('You are not a participant in this conversation');
        }

        broadcast(new \App\Events\UserTyping($conversation, $user))->toOthers();

        return ResponseHelper::success([], 'Typing indicator sent');
    }

    public function stopTyping(Request $request, $conversationId)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        // CRITICAL FIX: Use withoutTenantScope to find conversation, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $conversation = DirectMessageConversation::withoutTenantScope()->find($conversationId);
        
        if (!$conversation) {
            return ResponseHelper::notFound('Conversation not found');
        }
        
        // CRITICAL: Verify tenant scoping
        if ($conversation->merchant_id !== $merchantId) {
            return ResponseHelper::forbidden('You do not have access to this conversation');
        }

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return ResponseHelper::forbidden('You are not a participant in this conversation');
        }

        broadcast(new \App\Events\UserStoppedTyping($conversation, $user))->toOthers();

        return ResponseHelper::success([], 'Stop typing indicator sent');
    }

    /**
     * Edit a direct message
     */
    public function updateMessage($messageId, Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        // CRITICAL FIX: Use withoutTenantScope to find message, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $message = DirectMessage::withoutTenantScope()->find($messageId);
        
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping via conversation
        $conversation = $message->conversation;
        if ($conversation->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this message'
            ], 403);
        }

        // Check if user owns the message
        if ($message->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own messages'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $message->markAsEdited($request->content);

        // Broadcast message edited
        broadcast(new \App\Events\DirectMessageEdited($message))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $message->load(['user', 'reactions'])
        ]);
    }

    /**
     * Delete a direct message
     */
    public function deleteMessage($messageId)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        $message = DirectMessage::findOrFail($messageId);

        // Check if user owns the message
        if ($message->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own messages'
            ], 403);
        }

        $message->markAsDeleted();

        // Broadcast message deleted
        broadcast(new \App\Events\DirectMessageDeleted($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Add reaction to direct message
     */
    public function addReaction($messageId, Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        // CRITICAL FIX: Use withoutTenantScope to find message, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $message = DirectMessage::withoutTenantScope()->find($messageId);
        
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }
        
        // Check if user is participant
        $conversation = $message->conversation;
        
        // CRITICAL: Verify tenant scoping
        if ($conversation->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this message'
            ], 403);
        }
        
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $reaction = $message->addReaction($request->emoji, $user);

        // Broadcast reaction added
        broadcast(new \App\Events\DirectMessageReactionAdded($message, $reaction))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $reaction
        ]);
    }

    /**
     * Remove reaction from direct message
     */
    public function removeReaction($messageId, Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        // CRITICAL FIX: Use withoutTenantScope to find message, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $message = DirectMessage::withoutTenantScope()->find($messageId);
        
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping via conversation
        $conversation = $message->conversation;
        if ($conversation->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this message'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $message->removeReaction($request->emoji, $user);

        // Broadcast reaction removed
        broadcast(new \App\Events\DirectMessageReactionRemoved($message, $request->emoji, $user))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Reaction removed'
        ]);
    }

    /**
     * Leave group conversation
     */
    public function leaveConversation($conversationId)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found. Please contact administrator.'
            ], 404);
        }
        // CRITICAL FIX: Use withoutTenantScope to find conversation, then verify tenant scoping
        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $conversation = DirectMessageConversation::withoutTenantScope()->find($conversationId);
        
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }
        
        // CRITICAL: Verify tenant scoping
        if ($conversation->merchant_id !== $merchantId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this conversation'
            ], 403);
        }

        if (!$conversation->is_group) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot leave a direct message conversation'
            ], 400);
        }

        $conversation->removeParticipant($user);

        return response()->json([
            'success' => true,
            'message' => 'Successfully left conversation'
        ]);
    }
}