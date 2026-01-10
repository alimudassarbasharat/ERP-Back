<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class UserMentioned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mentionedUser;
    public $message;
    public $conversation;
    public $mentioner;

    public function __construct(User $mentionedUser, $message, $conversation, User $mentioner)
    {
        $this->mentionedUser = $mentionedUser;
        $this->message = $message;
        $this->conversation = $conversation;
        $this->mentioner = $mentioner;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->mentionedUser->id);
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'conversation_type' => $this->conversation instanceof \App\Models\DirectMessageConversation ? 'dm' : 'channel',
            'conversation_name' => $this->conversation->name ?? $this->conversation->display_name,
            'mentioner' => [
                'id' => $this->mentioner->id,
                'name' => $this->mentioner->name,
                'avatar' => $this->mentioner->avatar
            ],
            'message_preview' => substr($this->message->content, 0, 100),
            'created_at' => $this->message->created_at->toIso8601String()
        ];
    }

    public function broadcastAs()
    {
        return 'user.mentioned';
    }
}
