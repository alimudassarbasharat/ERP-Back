<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Send a message to a channel
     */
    public function sendToChannel($channelId, Request $request)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $channel = Channel::findOrFail($channelId);

        // Check if user is member
        if (!$channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required_without:attachments|string|max:5000',
            'parent_id' => 'nullable|exists:messages,id',
            'attachments' => 'array|max:10',
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip',
                'max:10240' // 10MB max
            ],
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Sanitize content to prevent XSS
        $content = $request->content ?? '';
        if ($content) {
            // Remove script tags and event handlers
            $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
            $content = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
            // Allow only safe tags
            $allowed = '<b><strong><i><em><u><code><pre><br><p><a>';
            $content = strip_tags($content, $allowed);
        }

        // Extract mentions from content
        $mentions = $this->extractMentions($content);
        
        // Create message
        $message = Message::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'content' => $content,
            'parent_id' => $request->parent_id,
            'type' => $request->hasFile('attachments') ? 'file' : 'text',
            'metadata' => array_merge($request->metadata ?? [], ['mentions' => $mentions])
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('message-attachments/' . date('Y/m'), 'public');
                
                $attachment = MessageAttachment::create([
                    'message_id' => $message->id,
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

                // Generate thumbnail for images
                if ($attachment->isImage()) {
                    // Thumbnail generation logic here
                }
            }
        }

        // Update channel's updated_at
        $channel->touch();

        // Increment unread count for other members
        $channel->users()
            ->where('user_id', '!=', $user->id)
            ->each(function ($member) use ($channel) {
                $channel->incrementUnreadCount($member);
            });

        // Load relationships
        $message->load(['user:id,name,avatar', 'attachments', 'reactions']);

        // Broadcast message
        broadcast(new \App\Events\MessageSent($message, $channel))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $message
        ], 201);
    }

    /**
     * Get message thread
     */
    public function getThread($messageId)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        $message = Message::with(['user', 'channel'])->findOrFail($messageId);

        // Check if user can view this message
        if (!$message->channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $replies = $message->replies()
            ->with(['user:id,name,avatar', 'reactions', 'attachments'])
            ->oldest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => [
                'parent' => $message,
                'replies' => $replies
            ]
        ]);
    }

    /**
     * Edit a message
     */
    public function update($id, Request $request)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        $message = Message::findOrFail($id);

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
        broadcast(new \App\Events\MessageEdited($message))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $message->load(['user', 'reactions'])
        ]);
    }

    /**
     * Delete a message
     */
    public function destroy($id)
    {
        $authUser = Auth::user();
        $user = $authUser instanceof \App\Models\Admin ? $authUser->user : $authUser;
        $message = Message::findOrFail($id);

        // Check permissions
        $channel = $message->channel;
        $member = $channel->users()->where('user_id', $user->id)->first();
        
        if ($message->user_id !== $user->id && (!$member || $member->pivot->role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this message'
            ], 403);
        }

        $message->markAsDeleted();

        // Broadcast message deleted
        broadcast(new \App\Events\MessageDeleted($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Add reaction to message
     */
    public function addReaction($id, Request $request)
    {
        $user = Auth::user();
        $message = Message::findOrFail($id);

        // Check if user can react
        if (!$message->channel->isUserMember($user)) {
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
        broadcast(new \App\Events\ReactionAdded($message, $reaction))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $reaction
        ]);
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction($id, Request $request)
    {
        $user = Auth::user();
        $message = Message::findOrFail($id);

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
        broadcast(new \App\Events\ReactionRemoved($message, $request->emoji, $user))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Reaction removed'
        ]);
    }

    /**
     * Search messages
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'channel_id' => 'nullable|exists:channels,id',
            'user_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get user's accessible channels
        $channelIds = $user->channels()->pluck('channels.id');

        $query = Message::whereIn('channel_id', $channelIds)
            ->where('content', 'like', '%' . $request->query . '%')
            ->where('is_deleted', false);

        if ($request->channel_id) {
            $query->where('channel_id', $request->channel_id);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $messages = $query->with(['user:id,name,avatar', 'channel:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }
    
    /**
     * Extract mentions from message content
     */
    private function extractMentions($content)
    {
        $mentions = [];
        
        // Match @username or @"Full Name" patterns
        preg_match_all('/@(\w+|"[^"]+")/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                // Remove quotes if present
                $username = trim($match, '"');
                
                // Find user by username or name
                $user = \App\Models\User::where('username', $username)
                    ->orWhere('name', $username)
                    ->first();
                
                if ($user) {
                    $mentions[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username ?? $user->name
                    ];
                }
            }
        }
        
        return array_unique($mentions, SORT_REGULAR);
    }
}