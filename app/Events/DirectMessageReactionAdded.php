<?php

namespace App\Events;

use App\Models\Message;
use App\Models\MessageReaction;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class DirectMessageReactionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $reaction;

    public function __construct(Message $message, MessageReaction $reaction)
    {
        $this->message = $message;
        $this->reaction = $reaction;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dm.' . $this->message->conversation_id);
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'reaction' => $this->reaction->load('user:id,name,avatar')
        ];
    }

    public function broadcastAs()
    {
        return 'dm.reaction.added';
    }
}