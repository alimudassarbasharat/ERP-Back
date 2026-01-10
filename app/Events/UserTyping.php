<?php

namespace App\Events;

use App\Models\DirectMessageConversation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $user;

    public function __construct(DirectMessageConversation $conversation, User $user)
    {
        $this->conversation = $conversation;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dm.' . $this->conversation->id);
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'user.typing';
    }
}
