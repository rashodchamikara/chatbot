<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class OmnichannelMessageChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Message $message,
        public string $changeType = 'updated'
    ) {
        $this->message->loadMissing(
            'conversation'
        );

        if (!$this->message->conversation) {
            throw new RuntimeException(
                'Cannot broadcast an omnichannel message without a conversation.'
            );
        }
    }

    public function broadcastOn(): array
    {
        $conversation =
            $this->message->conversation;

        return [
            new PrivateChannel(
                "tenant.{$conversation->tenant_id}.inbox"
            ),

            new PrivateChannel(
                "tenant.{$conversation->tenant_id}.conversation.{$conversation->id}"
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'omnichannel.message.changed';
    }

    public function broadcastWith(): array
    {
        $conversation =
            $this->message->conversation;

        return [
            'change_type' =>
                $this->changeType,

            'message' => [
                'id' =>
                    $this->message->id,

                'conversation_id' =>
                    $this->message->conversation_id,

                'channel_connection_id' =>
                    $this->message->channel_connection_id,

                'external_message_id' =>
                    $this->message->external_message_id,

                'direction' =>
                    $this->message->direction,

                'sender_type' =>
                    $this->message->sender_type,

                'message_type' =>
                    $this->message->message_type,

                'message' =>
                    $this->message->message,

                'status' =>
                    $this->message->status,

                'is_ai_generated' =>
                    (bool) $this->message->is_ai_generated,

                'sent_at' =>
                    $this->message->sent_at
                        ? $this->message
                            ->sent_at
                            ->toIso8601String()
                        : null,

                'created_at' =>
                    $this->message->created_at
                        ? $this->message
                            ->created_at
                            ->toIso8601String()
                        : null,
            ],

            'conversation' => [
                'id' =>
                    $conversation->id,

                'tenant_id' =>
                    $conversation->tenant_id,

                'channel_connection_id' =>
                    $conversation->channel_connection_id,

                'contact_id' =>
                    $conversation->contact_id,

                'status' =>
                    $conversation->status,

                'mode' =>
                    $conversation->mode,

                'unread_count' =>
                    (int) $conversation->unread_count,

                'last_message_at' =>
                    $conversation->last_message_at
                        ? $conversation
                            ->last_message_at
                            ->toIso8601String()
                        : null,

                'last_inbound_at' =>
                    $conversation->last_inbound_at
                        ? $conversation
                            ->last_inbound_at
                            ->toIso8601String()
                        : null,

                'first_response_at' =>
                    $conversation->first_response_at
                        ? $conversation
                            ->first_response_at
                            ->toIso8601String()
                        : null,
            ],
        ];
    }
}