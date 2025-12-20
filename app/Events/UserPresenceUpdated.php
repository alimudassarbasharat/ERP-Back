<?php

namespace App\Events;

use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class UserPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $presence;
    public $channelId;

    public function __construct(User $user, UserPresence $presence, $channelId = null)
    {
        $this->user = $user;
        $this->presence = $presence;
        $this->channelId = $channelId;
    }

    public function broadcastOn()
    {
        $channels = [new PrivateChannel('workspace.presence')];
        
        if ($this->channelId) {
            $channels[] = new PrivateChannel('channel.' . $this->channelId);
        }
        
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user->only(['id', 'name', 'avatar']),
            'status' => $this->presence->status,
            'last_seen' => $this->presence->last_seen,
            'channel_id' => $this->channelId
        ];
    }

    public function broadcastAs()
    {
        return 'presence.updated';
    }
}