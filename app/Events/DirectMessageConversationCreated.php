<?php

namespace App\Events;

use App\Models\DirectMessageConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct(DirectMessageConversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function broadcastOn()
    {
        // CRITICAL: Broadcast to all participants so they see conversation in sidebar immediately
        $channels = [];
        foreach ($this->conversation->participants as $participant) {
            $channels[] = new PrivateChannel('user.' . $participant->id);
        }
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'conversation' => $this->conversation->load([
                'participants:id,name,avatar',
                'latestMessage.user:id,name,avatar'
            ])
        ];
    }

    public function broadcastAs()
    {
        return 'dm.conversation.created';
    }
}
