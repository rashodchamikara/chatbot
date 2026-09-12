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

    /**
     * The public website channel is for messages delivered TO the widget.
     *
     * Inbound visitor messages are already rendered locally by the widget and
     * are published separately to the omnichannel/admin inbox. Broadcasting
     * them here causes the visitor's own text to appear as a reply.
     */
    public function broadcastWhen(): bool
    {
        if ($this->message->direction === 'inbound') {
            return false;
        }

        return !in_array(
            $this->message->sender,
            [
                'visitor',
                'user',
            ],
            true
        );
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
