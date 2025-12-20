<?php

namespace App\Events;

use App\Models\Message;
use App\Models\DirectMessageConversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class DirectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversation;

    public function __construct(Message $message, DirectMessageConversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dm.' . $this->conversation->id);
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message->load(['user:id,name,avatar', 'attachments', 'reactions'])
        ];
    }

    public function broadcastAs()
    {
        return 'dm.message.sent';
    }
}