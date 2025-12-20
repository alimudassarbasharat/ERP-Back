<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Channel;
use Illuminate\Broadcasting\Channel as BroadcastChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $channel;

    public function __construct(Message $message, Channel $channel)
    {
        $this->message = $message;
        $this->channel = $channel;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel.' . $this->channel->id);
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message->load(['user:id,name,avatar', 'attachments', 'reactions'])
        ];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}