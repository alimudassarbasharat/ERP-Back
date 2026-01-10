<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnreadCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $conversationType; // 'channel' or 'dm'
    public $conversationId;
    public $unreadCount;

    public function __construct(User $user, string $conversationType, int $conversationId, int $unreadCount)
    {
        $this->user = $user;
        $this->conversationType = $conversationType;
        $this->conversationId = $conversationId;
        $this->unreadCount = $unreadCount;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->user->id);
    }

    public function broadcastWith()
    {
        return [
            'conversation_type' => $this->conversationType,
            'conversation_id' => $this->conversationId,
            'unread_count' => $this->unreadCount
        ];
    }

    public function broadcastAs()
    {
        return 'unread.updated';
    }
}
