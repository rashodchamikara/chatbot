<?php

namespace App\Services\Omnichannel;

use App\Data\Omnichannel\OutboundMessageData;
use App\Data\Omnichannel\SendResult;
use App\Events\OmnichannelMessageChanged;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
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

        if (!$conversation->channel_connection_id) {
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
        | Load channel connection
        |--------------------------------------------------------------------------
        */

        $conversation->loadMissing(
            'channelConnection'
        );

        $connection =
            $conversation->channelConnection;

        if (!$connection) {
            throw new RuntimeException(
                'Channel connection could not be found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tenant isolation
        |--------------------------------------------------------------------------
        */

        if (
            (int) $conversation->tenant_id !==
            (int) $connection->tenant_id
        ) {
            throw new RuntimeException(
                'Conversation and channel connection belong to different tenants.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve provider identity
        |--------------------------------------------------------------------------
        */

        $identity = ContactIdentity::query()
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
        | Save local pending message
        |--------------------------------------------------------------------------
        */

        $message = DB::transaction(
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
                $message = new Message();

                $message->conversation_id =
                    $conversation->id;

                $message->channel_connection_id =
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

                $message->status =
                    'pending';

                $message->is_ai_generated =
                    $isAiGenerated;

                if ($senderUserId !== null) {
                    $message->sender_user_id =
                        $senderUserId;
                }

                if ($replyToExternalId !== null) {
                    $message->external_reply_to_id =
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
        */

        $message->load(
            'conversation'
        );

        OmnichannelMessageChanged::dispatch(
            $message,
            'created'
        );

        /*
        |--------------------------------------------------------------------------
        | Build the actual DTO used by your project
        |--------------------------------------------------------------------------
        */

        $outbound = new OutboundMessageData(
            tenantId:
                (int) $conversation->tenant_id,

            conversationId:
                (int) $conversation->id,

            channelConnectionId:
                (int) $connection->id,

            externalContactId:
                (string) $identity->external_user_id,

            externalThreadId:
                $conversation->external_thread_id,

            messageType:
                $message->message_type,

            text:
                $body,

            attachments:
                $attachments,

            metadata:
                array_merge(
                    $metadata,
                    [
                        'message_id' =>
                            $message->id,

                        'reply_to_external_id' =>
                            $replyToExternalId,
                    ]
                ),
        );

        try {
            /*
            |--------------------------------------------------------------------------
            | Resolve correct adapter
            |--------------------------------------------------------------------------
            */

            $adapter =
                $this->channelManager->forConnection(
                    $connection
                );

            /*
            |--------------------------------------------------------------------------
            | Send
            |--------------------------------------------------------------------------
            */

            $result = $adapter->send(
                $connection,
                $outbound
            );

            if (!$result instanceof SendResult) {
                throw new RuntimeException(
                    'Channel adapter returned an invalid send result.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Provider returned a failure
            |--------------------------------------------------------------------------
            */

            if (!$result->successful) {
                $error =
                    $result->errorMessage
                    ?: 'The channel provider rejected the message.';

                if ($result->errorCode) {
                    $error .=
                        ' [' .
                        $result->errorCode .
                        ']';
                }

                throw new RuntimeException(
                    $error
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Mark message as sent
            |--------------------------------------------------------------------------
            */

            DB::transaction(
                function () use (
                    $message,
                    $conversation,
                    $result
                ): void {
                    $message->external_message_id =
                        $result->externalMessageId;

                    $message->status =
                        'sent';

                    $message->sent_at =
                        now();

                    $payload =
                        is_array($message->payload)
                            ? $message->payload
                            : [];

                    $payload['provider_metadata'] =
                        $result->metadata;

                    $message->payload =
                        $payload;

                    $message->save();

                    $conversation->last_message_at =
                        now();

                    if (!$conversation->first_response_at) {
                        $conversation->first_response_at =
                            now();
                    }

                    $conversation->save();
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Broadcast sent status
            |--------------------------------------------------------------------------
            */

            $message->refresh();

            $message->load(
                'conversation'
            );

            OmnichannelMessageChanged::dispatch(
                $message,
                'status_updated'
            );

            return $message;
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Preserve failed message
            |--------------------------------------------------------------------------
            */

            $message->status =
                'failed';

            $payload =
                is_array($message->payload)
                    ? $message->payload
                    : [];

            $payload['send_error'] = [
                'message' =>
                    $exception->getMessage(),

                'failed_at' =>
                    now()->toIso8601String(),
            ];

            $message->payload =
                $payload;

            $message->save();

            /*
            |--------------------------------------------------------------------------
            | Broadcast failure
            |--------------------------------------------------------------------------
            */

            $message->load(
                'conversation'
            );

            OmnichannelMessageChanged::dispatch(
                $message,
                'status_updated'
            );

            throw $exception;
        }
    }
}