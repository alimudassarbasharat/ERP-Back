<?php

namespace App\Events;

use App\Models\Channel;
use Illuminate\Broadcasting\Channel as BroadcastChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function broadcastOn()
    {
        // Broadcast to all channel members so they see it in sidebar
        $channels = [];
        foreach ($this->channel->users as $member) {
            $channels[] = new PrivateChannel('user.' . $member->id);
        }
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'channel' => $this->channel->load(['creator:id,name,avatar'])
        ];
    }

    public function broadcastAs()
    {
        return 'channel.created';
    }
}