<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ConversationMessageCreated implements ShouldBroadcastNow
{
   use Dispatchable, SerializesModels;

    public function __construct(
        public Message $message
    ) {
        $this->message->loadMissing([
            'conversation.website',
            'user',
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('conversation.' . $this->message->conversation->realtime_token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' =>
                $this->message->conversation_id,

            'sender' => $this->message->sender,

            'message' => $this->message->message,

            'is_system' =>
                (bool) $this->message->is_system,

            'agent_name' =>
                $this->message->user?->name,

            'created_at' =>
                $this->message
                    ->created_at
                    ?->toISOString(),
        ];
    }
}
