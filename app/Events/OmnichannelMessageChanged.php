<?php

namespace App\Events;

use App\Models\Message;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use RuntimeException;
use Throwable;

class OmnichannelMessageChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Message $message,
        public string $changeType = 'updated'
    ) {
        /*
        |--------------------------------------------------------------------------
        | Load owning conversation
        |--------------------------------------------------------------------------
        */

        $this->message->loadMissing([
            'conversation',
            'user',
            'senderUser',
        ]);

        if (!$this->message->conversation) {
            throw new RuntimeException(
                'Cannot broadcast an omnichannel message without a conversation.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Omnichannel conversations require tenant ownership
        |--------------------------------------------------------------------------
        |
        | Legacy website conversations can still exist without tenant_id.
        | They continue using ConversationMessageCreated and the existing
        | public conversation realtime channel.
        |
        */

        if (!$this->message->conversation->tenant_id) {
            throw new RuntimeException(
                'Cannot broadcast an omnichannel message without tenant ownership.'
            );
        }
    }

    /**
     * Broadcast to:
     *
     * 1. Entire tenant inbox
     * 2. Specific open conversation
     */
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

    /**
     * Laravel Echo event name.
     */
    public function broadcastAs(): string
    {
        return 'omnichannel.message.changed';
    }

    /**
     * Keep the broadcast payload intentionally small.
     *
     * Do not broadcast:
     *
     * - credentials
     * - webhook payloads
     * - provider raw responses
     * - internal exceptions
     */
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

                /*
                 * Keep the legacy sender fields in the realtime payload while
                 * the existing admin chat renderer and the omnichannel schema
                 * coexist. These are additive and do not change the canonical
                 * omnichannel direction/sender_type fields.
                 */
                'sender' =>
                    $this->message->sender,

                'sender_type' =>
                    $this->message->sender_type,

                'sender_user_id' =>
                    $this->message->sender_user_id,

                'is_system' =>
                    (bool) $this->message->is_system,

                'agent_name' =>
                    $this->message->user?->name
                    ?? $this->message->senderUser?->name,

                'message_type' =>
                    $this->message->message_type,

                'message' =>
                    $this->message->message,

                'status' =>
                    $this->message->status,

                'is_ai_generated' =>
                    (bool) $this->message
                        ->is_ai_generated,

                'sent_at' =>
                    $this->formatDate(
                        $this->message->sent_at
                    ),

                'delivered_at' =>
                    $this->formatDate(
                        $this->message->delivered_at
                    ),

                'read_at' =>
                    $this->formatDate(
                        $this->message->read_at
                    ),

                'created_at' =>
                    $this->formatDate(
                        $this->message->created_at
                    ),

                'updated_at' =>
                    $this->formatDate(
                        $this->message->updated_at
                    ),
            ],

            'conversation' => [
                'id' =>
                    $conversation->id,

                'tenant_id' =>
                    $conversation->tenant_id,

                'ai_agent_id' =>
                    $conversation->ai_agent_id,

                'channel_connection_id' =>
                    $conversation->channel_connection_id,

                'contact_id' =>
                    $conversation->contact_id,

                'assigned_user_id' =>
                    $conversation->assigned_user_id,

                'status' =>
                    $conversation->status,

                'mode' =>
                    $conversation->mode,

                'priority' =>
                    $conversation->priority,

                'unread_count' =>
                    (int) $conversation
                        ->unread_count,

                'last_message_at' =>
                    $this->formatDate(
                        $conversation
                            ->last_message_at
                    ),

                'last_inbound_at' =>
                    $this->formatDate(
                        $conversation
                            ->last_inbound_at
                    ),

                'first_response_at' =>
                    $this->formatDate(
                        $conversation
                            ->first_response_at
                    ),
            ],
        ];
    }

    /**
     * Handles both Carbon casts and raw datetime strings.
     */
    private function formatDate(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        try {
            return Carbon::parse(
                $value
            )->toIso8601String();
        } catch (Throwable) {
            return (string) $value;
        }
    }
}