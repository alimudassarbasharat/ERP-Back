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
        // CRITICAL: Broadcast to user's private channel so they see channel in sidebar immediately
        // Also broadcast to channel so other members see the update
        return [
            new PrivateChannel('user.' . $this->user->id), // For the new member
            new PrivateChannel('channel.' . $this->channel->id) // For existing members
        ];
    }

    public function broadcastWith()
    {
        // CRITICAL FIX: Include channel data so frontend can add it to sidebar immediately
        return [
            'user' => $this->user->only(['id', 'name', 'avatar']),
            'channel' => $this->channel->load(['creator:id,name,avatar', 'latestMessage.user:id,name,avatar'])
        ];
    }

    public function broadcastAs()
    {
        return 'user.joined';
    }
}