<?php
namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationModeChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Conversation $conversation
    ) {
        $this->conversation->loadMissing(
            'assignedAgent'
        );
    }

    public function broadcastOn(): array
    {
        return [
            new Channel(
                'conversation.' .
                $this->conversation->realtime_token
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.mode.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' =>
                $this->conversation->id,

            'mode' =>
                $this->conversation->mode,

            'assigned_agent_id' =>
                $this->conversation->assigned_agent_id,

            'assigned_agent_name' =>
                $this->conversation
                    ->assignedAgent
                    ?->name,

            'live_requested_at' =>
                $this->conversation
                    ->live_requested_at
                    ?->toISOString(),

            'live_started_at' =>
                $this->conversation
                    ->live_started_at
                    ?->toISOString(),

            'live_ended_at' =>
                $this->conversation
                    ->live_ended_at
                    ?->toISOString(),
        ];
    }
}