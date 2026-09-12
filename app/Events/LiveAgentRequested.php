<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class LiveAgentRequested implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Conversation $conversation
    ) {
        $this->conversation->loadMissing(
            'website'
        );

        if (!$this->conversation->tenant_id) {
            throw new RuntimeException(
                'Cannot broadcast a live-agent request without tenant ownership.'
            );
        }
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'tenant.'
                . $this->conversation->tenant_id
                . '.live'
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'live.agent.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' =>
                $this->conversation->id,

            'visitor_id' =>
                $this->conversation->visitor_id,

            'website_id' =>
                $this->conversation->website_id,

            'website_name' =>
                $this->conversation
                    ->website
                    ?->name,

            'mode' =>
                $this->conversation->mode,

            'requested_at' =>
                $this->conversation
                    ->live_requested_at
                    ?->toDateTimeString(),

            /*
             * Do not let a missing/renamed admin route turn a successfully
             * persisted live-agent request into a public API 500.
             */
            'url' =>
                Route::has(
                    'admin.conversations.show'
                )
                    ? route(
                        'admin.conversations.show',
                        [
                            'conversation' =>
                                $this->conversation->id,
                        ]
                    )
                    : null,
        ];
    }
}
