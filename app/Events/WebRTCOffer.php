<?php

namespace App\Events;

use App\Models\DirectMessageConversation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class WebRTCOffer implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $user;
    public $offer;

    public function __construct(DirectMessageConversation $conversation, User $user, array $offer)
    {
        $this->conversation = $conversation;
        $this->user = $user;
        $this->offer = $offer;
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
            'offer' => $this->offer
        ];
    }

    public function broadcastAs()
    {
        return 'webrtc.offer';
    }
}
