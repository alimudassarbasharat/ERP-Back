<?php

namespace App\Events;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class DirectMessageReactionRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $emoji;
    public $user;

    public function __construct(DirectMessage $message, $emoji, User $user)
    {
        $this->message = $message;
        $this->emoji = $emoji;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dm.' . $this->message->conversation_id);
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'emoji' => $this->emoji,
            'user' => $this->user->only(['id', 'name', 'avatar'])
        ];
    }

    public function broadcastAs()
    {
        return 'dm.reaction.removed';
    }
}