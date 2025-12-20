<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class UserStoppedTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $type;
    public $conversationId;

    public function __construct(User $user, $type, $conversationId)
    {
        $this->user = $user;
        $this->type = $type;
        $this->conversationId = $conversationId;
    }

    public function broadcastOn()
    {
        $channelName = $this->type === 'channel' 
            ? 'channel.' . $this->conversationId 
            : 'dm.' . $this->conversationId;
            
        return new PrivateChannel($channelName);
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user->only(['id', 'name', 'avatar']),
            'type' => $this->type
        ];
    }

    public function broadcastAs()
    {
        return 'user.stopped_typing';
    }
}