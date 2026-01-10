<?php

namespace App\Events;

use App\Models\DirectMessage;
use App\Models\DirectMessageConversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $recipient;
    public $message;
    public $conversation;
    public $sender;

    public function __construct(User $recipient, DirectMessage $message, DirectMessageConversation $conversation, User $sender)
    {
        $this->recipient = $recipient;
        $this->message = $message;
        $this->conversation = $conversation;
        $this->sender = $sender;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->recipient->id);
    }

    public function broadcastWith()
    {
        // CRITICAL FIX: Include full conversation data so frontend can add it to sidebar if not present
        // Get unread count from pivot table
        $participantPivot = $this->conversation->participants()
            ->where('user_id', $this->recipient->id)
            ->first();
        
        $unreadCount = $participantPivot ? $participantPivot->pivot->unread_count : 0;
        
        // Load conversation with all necessary relationships
        $conversation = $this->conversation->load([
            'participants:id,name,avatar',
            'latestMessage.user:id,name,avatar'
        ]);
        
        // Format conversation for frontend (matching the format from conversations endpoint)
        $conversationData = [
            'id' => $conversation->id,
            'name' => $conversation->name,
            'is_group' => $conversation->is_group,
            'merchant_id' => $conversation->merchant_id,
            'display_name' => $conversation->display_name,
            'participants' => $conversation->participants->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'avatar' => $p->avatar
                ];
            }),
            'latest_message' => $conversation->latestMessage ? [
                'id' => $conversation->latestMessage->id,
                'content' => $conversation->latestMessage->content,
                'created_at' => $conversation->latestMessage->created_at,
                'user' => $conversation->latestMessage->user ? [
                    'id' => $conversation->latestMessage->user->id,
                    'name' => $conversation->latestMessage->user->name,
                    'avatar' => $conversation->latestMessage->user->avatar
                ] : null
            ] : null,
            'unread_count' => $unreadCount,
            'updated_at' => $conversation->updated_at
        ];
        
        return [
            'notification' => [
                'id' => $this->message->id,
                'conversation_id' => $this->conversation->id,
                'conversation_name' => $this->conversation->display_name ?? 'Direct Message',
                'sender' => [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'avatar' => $this->sender->avatar
                ],
                'message_preview' => substr($this->message->content ?? '', 0, 200),
                'created_at' => $this->message->created_at->toISOString()
            ],
            'conversation' => $conversationData,
            'unread_count' => $unreadCount
        ];
    }

    public function broadcastAs()
    {
        return 'dm.notification';
    }
}
