<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $recipient;
    public $message;
    public $channel;
    public $sender;

    public function __construct(User $recipient, Message $message, Channel $channel, User $sender)
    {
        $this->recipient = $recipient;
        $this->message = $message;
        $this->channel = $channel;
        $this->sender = $sender;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->recipient->id);
    }

    public function broadcastWith()
    {
        return [
            'notification' => [
                'id' => $this->message->id,
                'conversation_id' => $this->channel->id,
                'conversation_name' => $this->channel->name,
                'sender' => [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'avatar' => $this->sender->avatar
                ],
                'message_preview' => substr($this->message->content ?? '', 0, 200),
                'created_at' => $this->message->created_at->toISOString()
            ],
            'unread_count' => $this->channel->users()
                ->where('user_id', $this->recipient->id)
                ->first()
                ->pivot
                ->unread_count ?? 0
        ];
    }

    public function broadcastAs()
    {
        return 'channel.notification';
    }
}
