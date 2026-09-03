<?php

namespace App\Services\Omnichannel;

use App\Data\Omnichannel\OutboundMessageData;
use App\Data\Omnichannel\SendResult;
use App\Events\OmnichannelMessageChanged;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OutboundMessageService
{
    public function __construct(
        protected ChannelManager $channelManager
    ) {
    }

    public function send(
        Conversation $conversation,
        string $body,
        string $senderType = 'agent',
        ?int $senderUserId = null,
        bool $isAiGenerated = false,
        array $attachments = [],
        array $metadata = [],
        ?string $replyToExternalId = null,
    ): Message {
        /*
        |--------------------------------------------------------------------------
        | Validate conversation
        |--------------------------------------------------------------------------
        */

        if (
            !$conversation
                ->channel_connection_id
        ) {
            throw new RuntimeException(
                'Conversation does not have a channel connection.'
            );
        }

        if (!$conversation->contact_id) {
            throw new RuntimeException(
                'Conversation does not have a contact.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve channel connection
        |--------------------------------------------------------------------------
        */

        $conversation->loadMissing(
            'channelConnection'
        );

        $connection =
            $conversation
                ->channelConnection;

        if (!$connection) {
            throw new RuntimeException(
                'Channel connection could not be found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tenant boundary validation
        |--------------------------------------------------------------------------
        */

        if (
            (int) $conversation->tenant_id
            !==
            (int) $connection->tenant_id
        ) {
            throw new RuntimeException(
                'Conversation and channel connection belong to different tenants.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve provider contact identity
        |--------------------------------------------------------------------------
        */

        $identity =
            ContactIdentity::query()
                ->where(
                    'tenant_id',
                    $conversation->tenant_id
                )
                ->where(
                    'contact_id',
                    $conversation->contact_id
                )
                ->where(
                    'channel_connection_id',
                    $connection->id
                )
                ->first();

        if (!$identity) {
            throw new RuntimeException(
                'No contact identity exists for this conversation and channel.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Persist pending outbound message
        |--------------------------------------------------------------------------
        */

        $message =
            DB::transaction(
                function () use (
                    $conversation,
                    $connection,
                    $body,
                    $senderType,
                    $senderUserId,
                    $isAiGenerated,
                    $attachments,
                    $metadata,
                    $replyToExternalId
                ): Message {
                    $message =
                        new Message();

                    $message->conversation_id =
                        $conversation->id;

                    $message
                        ->channel_connection_id =
                        $connection->id;

                    $message->direction =
                        'outbound';

                    $message->sender_type =
                        $senderType;

                    $message->message_type =
                        empty($attachments)
                            ? 'text'
                            : 'attachment';

                    $message->message =
                        $body;

                    $message
                        ->is_ai_generated =
                        $isAiGenerated;

                    /*
                     * The frontend should see the message
                     * immediately even before the provider
                     * confirms delivery.
                     */
                    $message->status =
                        'pending';

                    if (
                        $senderUserId !== null
                    ) {
                        $message
                            ->sender_user_id =
                            $senderUserId;
                    }

                    if (
                        $replyToExternalId
                        !== null
                    ) {
                        $message
                            ->external_reply_to_id =
                            $replyToExternalId;
                    }

                    $message->payload = [
                        'attachments' =>
                            $attachments,

                        'metadata' =>
                            $metadata,
                    ];

                    $message->save();

                    return $message;
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Broadcast pending message
        |--------------------------------------------------------------------------
        |
        | The inbox can immediately show:
        |
        | "Hello"
        | Sending...
        |
        */

        $this->broadcastMessageChange(
            message:
                $message,

            changeType:
                'created'
        );

        /*
        |--------------------------------------------------------------------------
        | Build adapter-neutral outbound DTO
        |--------------------------------------------------------------------------
        */

        $outbound =
            new OutboundMessageData(
                externalUserId:
                    $identity
                        ->external_user_id,

                externalThreadId:
                    $conversation
                        ->external_thread_id,

                type:
                    $message
                        ->message_type,

                body:
                    $body,

                attachments:
                    $attachments,

                metadata:
                    array_merge(
                        $metadata,
                        [
                            'conversation_id' =>
                                $conversation->id,

                            'message_id' =>
                                $message->id,
                        ]
                    ),

                replyToExternalId:
                    $replyToExternalId,
            );

        try {
            /*
            |--------------------------------------------------------------------------
            | Resolve adapter
            |--------------------------------------------------------------------------
            |
            | ChannelManager decides which adapter handles
            | this ChannelConnection.
            |
            */

            $adapter =
                $this->channelManager
                    ->forConnection(
                        $connection
                    );

            /*
            |--------------------------------------------------------------------------
            | Provider send
            |--------------------------------------------------------------------------
            */

            $result =
                $adapter->send(
                    $connection,
                    $outbound
                );

            if (
                !$result instanceof SendResult
            ) {
                throw new RuntimeException(
                    'Channel adapter returned an invalid send result.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Persist provider result
            |--------------------------------------------------------------------------
            */

            DB::transaction(
                function () use (
                    $message,
                    $conversation,
                    $result
                ): void {
                    $message
                        ->external_message_id =
                        $result
                            ->externalMessageId;

                    $message->status =
                        $result->status;

                    $payload =
                        is_array(
                            $message->payload
                        )
                            ? $message->payload
                            : [];

                    /*
                     * Keep the raw provider response in
                     * storage for diagnostics.
                     *
                     * It is intentionally NOT broadcast
                     * to browsers.
                     */
                    $payload[
                        'provider_response'
                    ] =
                        $result->rawResponse;

                    $message->payload =
                        $payload;

                    if (
                        in_array(
                            $result->status,
                            [
                                'sent',
                                'delivered',
                                'success',
                            ],
                            true
                        )
                    ) {
                        $message->sent_at =
                            now();
                    }

                    $message->save();

                    /*
                     * Update conversation activity.
                     */
                    $conversation
                        ->last_message_at =
                        now();

                    /*
                     * First response measures how long it
                     * took the business/AI to respond.
                     */
                    if (
                        !$conversation
                            ->first_response_at
                    ) {
                        $conversation
                            ->first_response_at =
                            now();
                    }

                    $conversation->save();
                }
            );

            

            $this->broadcastMessageChange(
                message:
                    $message,

                changeType:
                    'status_updated'
            );

            return $message->fresh();
        } catch (Throwable $exception) {
            

            $message->status =
                'failed';

            $payload =
                is_array(
                    $message->payload
                )
                    ? $message->payload
                    : [];

            $payload['send_error'] = [
                'message' =>
                    $exception
                        ->getMessage(),

                'failed_at' =>
                    now()
                        ->toIso8601String(),
            ];

            $message->payload =
                $payload;

            $message->save();

            

            $this->broadcastMessageChange(
                message:
                    $message,

                changeType:
                    'status_updated'
            );

            throw $exception;
        }
    }

    protected function broadcastMessageChange(
        Message $message,
        string $changeType
    ): void {
        try {
            $message->refresh();

            $message->load(
                'conversation'
            );

            OmnichannelMessageChanged::dispatch(
                $message,
                $changeType
            );
        } catch (Throwable $exception) {
            Log::warning(
                'Omnichannel realtime broadcast failed.',
                [
                    'message_id' =>
                        $message->id,

                    'change_type' =>
                        $changeType,

                    'error' =>
                        $exception
                            ->getMessage(),
                ]
            );
        }
    }
}