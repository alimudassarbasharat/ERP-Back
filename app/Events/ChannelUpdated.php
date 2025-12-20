<?php

namespace App\Events;

use App\Models\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class ChannelUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel.' . $this->channel->id);
    }

    public function broadcastWith()
    {
        return [
            'channel' => $this->channel
        ];
    }

    public function broadcastAs()
    {
        return 'channel.updated';
    }
}