<?php

namespace App\Events;

use App\Models\DirectMessageConversation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $caller;
    public $type;

    public function __construct(DirectMessageConversation $conversation, User $caller, string $type)
    {
        $this->conversation = $conversation;
        $this->caller = $caller;
        $this->type = $type;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dm.' . $this->conversation->id);
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'caller' => [
                'id' => $this->caller->id,
                'name' => $this->caller->name,
                'avatar' => $this->caller->avatar
            ],
            'type' => $this->type
        ];
    }

    public function broadcastAs()
    {
        return 'call.initiated';
    }
}
