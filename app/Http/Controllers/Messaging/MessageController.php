<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Get messages for a channel
     */
    public function index(Request $request, $channelId)
    {
        $user = Auth::user();
        $channel = Channel::findOrFail($channelId);

        // Check if user is a member
        if (!$channel->isMember($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        $perPage = $request->get('per_page', 50);
        $beforeId = $request->get('before_id');
        $afterId = $request->get('after_id');

        $query = $channel->messages()
            ->with(['user', 'reactions.user', 'attachments', 'replies.user'])
            ->mainMessages()
            ->orderBy('created_at', 'desc');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        $messages = $query->paginate($perPage);

        // Mark as read
        $channel->members()->where('user_id', $user->id)->update([
            'last_read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Send a message to a channel
     */
    public function store(Request $request, $channelId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
            'type' => 'nullable|in:text,image,file,system',
            'parent_id' => 'nullable|exists:messages,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240' // 10MB max
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

        // Check if user is a member
        if (!$channel->isMember($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        $message = $channel->messages()->create([
            'user_id' => $user->id,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
            'parent_id' => $request->parent_id,
            'metadata' => []
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('message-attachments', $filename, 'public');
                
                $message->attachments()->create([
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'file_path' => $path,
                    'file_url' => Storage::url($path),
                    'metadata' => $this->getFileMetadata($file)
                ]);
            }
        }

        $message->load(['user', 'reactions', 'attachments', 'parent']);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }

    /**
     * Update a message
     */
    public function update(Request $request, $channelId, $messageId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $message = Message::findOrFail($messageId);

        // Check if user owns the message
        if ($message->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own messages'
            ], 403);
        }

        $message->update([
            'content' => $request->content,
            'is_edited' => true,
            'edited_at' => now()
        ]);

        $message->load(['user', 'reactions', 'attachments']);

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully',
            'data' => $message
        ]);
    }

    /**
     * Delete a message
     */
    public function destroy($channelId, $messageId)
    {
        $user = Auth::user();
        $message = Message::findOrFail($messageId);
        $channel = Channel::findOrFail($channelId);

        // Check if user owns the message or is admin/moderator
        $membership = $channel->members()->where('user_id', $user->id)->first();
        $canDelete = $message->user_id === $user->id || 
                    ($membership && in_array($membership->role, ['admin', 'moderator']));

        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete this message'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Add reaction to message
     */
    public function addReaction(Request $request, $channelId, $messageId)
    {
        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $message = Message::findOrFail($messageId);

        // Check if user is a member of the channel
        if (!$message->channel->isMember($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        // Check if user already reacted with this emoji
        $existingReaction = $message->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $request->emoji)
            ->first();

        if ($existingReaction) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reacted with this emoji'
            ], 400);
        }

        $reaction = $message->reactions()->create([
            'user_id' => $user->id,
            'emoji' => $request->emoji
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reaction added successfully',
            'data' => $reaction
        ]);
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction(Request $request, $channelId, $messageId)
    {
        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $message = Message::findOrFail($messageId);

        $reaction = $message->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $request->emoji)
            ->first();

        if (!$reaction) {
            return response()->json([
                'success' => false,
                'message' => 'Reaction not found'
            ], 404);
        }

        $reaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reaction removed successfully'
        ]);
    }

    /**
     * Pin/unpin a message
     */
    public function togglePin($channelId, $messageId)
    {
        $user = Auth::user();
        $message = Message::findOrFail($messageId);
        $channel = Channel::findOrFail($channelId);

        // Check if user is admin or moderator
        $membership = $channel->members()->where('user_id', $user->id)->first();
        if (!$membership || !in_array($membership->role, ['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins and moderators can pin messages'
            ], 403);
        }

        $message->update([
            'is_pinned' => !$message->is_pinned,
            'pinned_at' => $message->is_pinned ? null : now()
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->is_pinned ? 'Message pinned successfully' : 'Message unpinned successfully',
            'data' => $message
        ]);
    }

    /**
     * Get thread replies for a message
     */
    public function getThreadReplies($channelId, $messageId)
    {
        $user = Auth::user();
        $message = Message::findOrFail($messageId);

        // Check if user is a member of the channel
        if (!$message->channel->isMember($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this channel'
            ], 403);
        }

        $replies = $message->replies()
            ->with(['user', 'reactions.user', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $replies
        ]);
    }

    /**
     * Get file metadata
     */
    private function getFileMetadata($file)
    {
        $metadata = [];
        
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
            }
        }

        return $metadata;
    }
} 