<?php

namespace App\Events;

use App\Models\DirectMessageConversation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class IceCandidateReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $user;
    public $candidate;

    public function __construct(DirectMessageConversation $conversation, User $user, array $candidate)
    {
        $this->conversation = $conversation;
        $this->user = $user;
        $this->candidate = $candidate;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dm.' . $this->conversation->id);
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'candidate' => $this->candidate
        ];
    }

    public function broadcastAs()
    {
        return 'webrtc.ice-candidate';
    }
}
