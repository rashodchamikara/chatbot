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

class LiveAgentRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation
    ) {
        $this->conversation->loadMissing('website');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->conversation->website->tenant_id . '.live'),
        ];
    }
    public function broadcastAs(): string
    {
        return 'live.agent.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'visitor_id' => $this->conversation->visitor_id,
            'website_id' => $this->conversation->website_id,
            'website_name' => $this->conversation->website?->name,
            'mode' => $this->conversation->mode,
            'requested_at' => optional($this->conversation->live_requested_at)->toDateTimeString(),
            'url' => route('admin.conversations.show', [
                'conversation' => $this->conversation->id,
            ]),
        ];
    }
}
