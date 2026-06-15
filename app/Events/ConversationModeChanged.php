<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Conversation;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class ConversationModeChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation
    ) {
        $this->conversation->loadMissing('assignedAgent');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('conversation.' . $this->conversation->realtime_token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.mode.changed';
    }
    
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'mode' => $this->conversation->mode,
            'assigned_agent_id' => $this->conversation->assigned_agent_id,
            'assigned_agent_name' => $this->conversation->assignedAgent?->name,
        ];
    }
}
