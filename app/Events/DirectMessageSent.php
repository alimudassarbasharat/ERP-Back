<?php

namespace App\Events;

use App\Models\DirectMessage;
use App\Models\DirectMessageConversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Facades\Log;

class DirectMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversation;

    public function __construct(DirectMessage $message, DirectMessageConversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
        
        // CRITICAL: Log event creation for debugging
        Log::info('[Broadcast] DirectMessageSent event created', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'user_id' => $message->user_id,
            'broadcast_driver' => config('broadcasting.default')
        ]);
    }

    public function broadcastOn()
    {
        $channelName = 'dm.' . $this->conversation->id;
        
        // CRITICAL: Log channel name for debugging
        Log::info('[Broadcast] DirectMessageSent broadcasting on channel', [
            'channel' => $channelName,
            'conversation_id' => $this->conversation->id
        ]);
        
        return new PrivateChannel($channelName);
    }

    public function broadcastWith()
    {
        // CRITICAL: Load message with all relationships before broadcasting
        $message = $this->message->load(['user:id,name,avatar', 'attachments', 'reactions']);
        
        $payload = [
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'content' => $message->content,
                'type' => $message->type,
                'is_deleted' => $message->is_deleted ?? false,
                'is_edited' => $message->is_edited ?? false,
                'created_at' => $message->created_at?->toISOString() ?? now()->toISOString(),
                'updated_at' => $message->updated_at?->toISOString() ?? now()->toISOString(),
                'user' => $message->user ? [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->avatar
                ] : null,
                'attachments' => $message->attachments ? $message->attachments->map(function($attachment) {
                    return [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'original_name' => $attachment->original_name,
                        'file_url' => $attachment->file_url,
                        'mime_type' => $attachment->mime_type,
                        'file_size' => $attachment->file_size
                    ];
                })->toArray() : [],
                'reactions' => $message->reactions ? $message->reactions->map(function($reaction) {
                    return [
                        'id' => $reaction->id,
                        'emoji' => $reaction->emoji,
                        'user_id' => $reaction->user_id
                    ];
                })->toArray() : []
            ]
        ];
        
        // CRITICAL: Log payload for debugging
        Log::info('[Broadcast] DirectMessageSent payload prepared', [
            'message_id' => $message->id,
            'has_user' => !is_null($payload['message']['user']),
            'attachments_count' => count($payload['message']['attachments']),
            'reactions_count' => count($payload['message']['reactions'])
        ]);
        
        return $payload;
    }

    public function broadcastAs()
    {
        return 'dm.message.sent';
    }
}