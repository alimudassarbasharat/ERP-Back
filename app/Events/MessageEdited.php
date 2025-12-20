<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class MessageEdited implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel.' . $this->message->channel_id);
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message->load(['user:id,name,avatar', 'attachments', 'reactions'])
        ];
    }

    public function broadcastAs()
    {
        return 'message.edited';
    }
}