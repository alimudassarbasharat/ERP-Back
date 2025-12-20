<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use App\Models\DirectMessageConversation;
use App\Models\DirectMessageAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DirectMessageController extends Controller
{
    /**
     * Get all direct message conversations for the authenticated user
     */
    public function conversations()
    {
        $user = Auth::user();
        
        $conversations = $user->directMessageConversations()
            ->with([
                'participants.presence',
                'latestMessage.user:id,name,avatar'
            ])
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();

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
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id|different:' . Auth::id(),
            'name' => 'nullable|string|max:255' // For group DMs
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $userIds = array_merge($request->user_ids, [Auth::id()]);
        $isGroup = count($userIds) > 2;

        if (!$isGroup) {
            // For 1-on-1, find or create conversation
            $conversation = DirectMessageConversation::findOrCreateBetweenUsers(
                Auth::id(),
                $request->user_ids[0]
            );
        } else {
            // Create group conversation
            $conversation = DirectMessageConversation::create([
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
        $user = Auth::user();
        $conversation = DirectMessageConversation::findOrFail($conversationId);

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this conversation'
            ], 403);
        }

        // Mark as read
        $conversation->markAsRead($user);

        $messages = $conversation->messages()
            ->with([
                'user:id,name,avatar',
                'reactions',
                'attachments'
            ])
            ->latest()
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load('participants.presence'),
                'messages' => $messages
            ]
        ]);
    }

    /**
     * Send a direct message
     */
    public function sendMessage($conversationId, Request $request)
    {
        $user = Auth::user();
        $conversation = DirectMessageConversation::findOrFail($conversationId);

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this conversation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required_without:attachments|string|max:5000',
            'attachments' => 'array|max:10',
            'attachments.*' => 'file|max:10240', // 10MB max
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create message
        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $request->content ?? '',
            'type' => $request->hasFile('attachments') ? 'file' : 'text',
            'metadata' => $request->metadata
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('dm-attachments/' . date('Y/m'), 'public');
                
                DirectMessageAttachment::create([
                    'direct_message_id' => $message->id,
                    'filename' => $file->getClientOriginalName(),
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_url' => asset('storage/' . $path),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'metadata' => [
                        'extension' => $file->getClientOriginalExtension()
                    ]
                ]);
            }
        }

        // Update conversation's updated_at
        $conversation->touch();

        // Increment unread count for other participants
        $conversation->incrementUnreadCount($user);

        // Load relationships
        $message->load(['user:id,name,avatar', 'attachments', 'reactions']);

        // Broadcast message
        broadcast(new \App\Events\DirectMessageSent($message, $conversation))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $message
        ], 201);
    }

    /**
     * Edit a direct message
     */
    public function updateMessage($messageId, Request $request)
    {
        $user = Auth::user();
        $message = DirectMessage::findOrFail($messageId);

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
        $user = Auth::user();
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
        $user = Auth::user();
        $message = DirectMessage::findOrFail($messageId);

        // Check if user is participant
        $conversation = $message->conversation;
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
        $user = Auth::user();
        $message = DirectMessage::findOrFail($messageId);

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
        $user = Auth::user();
        $conversation = DirectMessageConversation::findOrFail($conversationId);

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