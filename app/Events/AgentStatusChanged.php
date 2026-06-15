<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Website;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;



class AgentStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Website $website,
        public bool $available
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('website.' . $this->website->realtime_token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'website_id' => $this->website->id,
            'available' => $this->available,
        ];
    }
}
