<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class DirectMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dm.' . $this->message->conversation_id);
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id
        ];
    }

    public function broadcastAs()
    {
        return 'dm.message.deleted';
    }
}