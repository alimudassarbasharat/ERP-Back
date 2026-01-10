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
        // CRITICAL: Broadcast to all channel members so sidebar updates for everyone
        $channels = [];
        foreach ($this->channel->users as $member) {
            $channels[] = new PrivateChannel('user.' . $member->id);
        }
        // Also broadcast to channel channel for real-time updates
        $channels[] = new PrivateChannel('channel.' . $this->channel->id);
        return $channels;
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