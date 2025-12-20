<?php

namespace App\Events;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Broadcasting\Channel as BroadcastChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinedChannel implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channel;
    public $user;

    public function __construct(Channel $channel, User $user)
    {
        $this->channel = $channel;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel.' . $this->channel->id);
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user->only(['id', 'name', 'avatar'])
        ];
    }

    public function broadcastAs()
    {
        return 'user.joined';
    }
}