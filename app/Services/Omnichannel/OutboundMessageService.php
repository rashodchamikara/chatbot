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
<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Validate conversation
        |--------------------------------------------------------------------------
        */

        if (
            !$conversation
                ->channel_connection_id
        ) {
=======
        if (!$conversation->channel_connection_id) {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            throw new RuntimeException(
                'Conversation does not have a channel connection.'
            );
        }

        if (!$conversation->contact_id) {
            throw new RuntimeException(
                'Conversation does not have a contact.'
            );
        }

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Resolve channel connection
        |--------------------------------------------------------------------------
        */

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        $conversation->loadMissing(
            'channelConnection'
        );

        $connection =
<<<<<<< HEAD
            $conversation
                ->channelConnection;
=======
            $conversation->channelConnection;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        if (!$connection) {
            throw new RuntimeException(
                'Channel connection could not be found.'
            );
        }

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Tenant boundary validation
        |--------------------------------------------------------------------------
        */

        if (
            (int) $conversation->tenant_id
            !==
            (int) $connection->tenant_id
=======
        if (
            (int) $conversation->tenant_id
            !== (int) $connection->tenant_id
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        ) {
            throw new RuntimeException(
                'Conversation and channel connection belong to different tenants.'
            );
        }

<<<<<<< HEAD
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
=======
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
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        if (!$identity) {
            throw new RuntimeException(
                'No contact identity exists for this conversation and channel.'
            );
        }

<<<<<<< HEAD
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
=======
        if (!$identity->external_user_id) {
            throw new RuntimeException(
                'Contact identity does not have an external provider user ID.'
            );
        }

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

                // Legacy website/live-chat compatibility.
                $message->user_id =
                    $senderUserId;

                $message->sender =
                    match ($senderType) {
                        'ai' => 'ai',
                        'system' => 'system',
                        default => 'agent',
                    };

                $message->role =
                    $senderType === 'ai'
                        ? 'assistant'
                        : null;

                $message->is_system =
                    $senderType === 'system';

                // Omnichannel fields.
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

                $message->is_ai_generated =
                    $isAiGenerated;

                $message->status =
                    'pending';

                $message->provider_status =
                    'pending';

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

        $this->broadcastMessageChange(
            message: $message,
            changeType: 'created'
        );

        $outbound =
            new OutboundMessageData(
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
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
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

<<<<<<< HEAD
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

=======
        $adapter =
            $this->channelManager
                ->forConnection(
                    $connection
                );

        try {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            $result =
                $adapter->send(
                    $connection,
                    $outbound
                );
<<<<<<< HEAD

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
=======
        } catch (Throwable $exception) {
            $this->markFailed(
                message: $message,
                exception: $exception
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            );

            throw $exception;
        }
<<<<<<< HEAD
=======

        if (!$result instanceof SendResult) {
            $exception =
                new RuntimeException(
                    'Channel adapter returned an invalid send result.'
                );

            $this->markFailed(
                message: $message,
                exception: $exception
            );

            throw $exception;
        }

        if (!$result->successful) {
            $this->markFailedFromResult(
                message: $message,
                result: $result
            );

            throw new RuntimeException(
                $result->errorMessage
                ?: 'Channel provider rejected the outbound message.'
            );
        }

        DB::transaction(
            function () use (
                $message,
                $conversation,
                $result
            ): void {
                $message->external_message_id =
                    $result->externalMessageId;

                $message->status =
                    $result->status ?: 'sent';

                $message->provider_status =
                    $result->status ?: 'sent';

                $message->error_code =
                    null;

                $message->error_message =
                    null;

                if (
                    in_array(
                        $message->status,
                        [
                            'sent',
                            'delivered',
                            'read',
                        ],
                        true
                    )
                ) {
                    $message->sent_at =
                        $message->sent_at ?: now();
                }

                if (
                    $message->status === 'delivered'
                ) {
                    $message->delivered_at =
                        $message->delivered_at ?: now();
                }

                if (
                    $message->status === 'read'
                ) {
                    $message->read_at =
                        $message->read_at ?: now();
                }

                $payload =
                    is_array($message->payload)
                        ? $message->payload
                        : [];

                $payload['provider_response'] =
                    $result->rawResponse;

                $payload['provider_metadata'] =
                    $result->metadata;

                $message->payload =
                    $payload;

                $message->save();

                $conversation->last_message_at =
                    now();

                if (
                    !$conversation->first_response_at
                ) {
                    $conversation->first_response_at =
                        now();
                }

                $conversation->save();
            }
        );

        $this->broadcastMessageChange(
            message: $message,
            changeType: 'status_updated'
        );

        return $message->fresh([
            'conversation',
            'attachments',
        ]);
    }

    protected function markFailed(
        Message $message,
        Throwable $exception
    ): void {
        $message->status =
            'failed';

        $message->provider_status =
            'failed';

        $message->error_message =
            $exception->getMessage();

        $payload =
            is_array($message->payload)
                ? $message->payload
                : [];

        $payload['send_error'] = [
            'message' =>
                $exception->getMessage(),

            'exception' =>
                get_class($exception),

            'failed_at' =>
                now()->toIso8601String(),
        ];

        $message->payload =
            $payload;

        $message->save();

        $this->broadcastMessageChange(
            message: $message,
            changeType: 'status_updated'
        );
    }

    protected function markFailedFromResult(
        Message $message,
        SendResult $result
    ): void {
        $message->status =
            'failed';

        $message->provider_status =
            $result->status ?: 'failed';

        $message->error_code =
            $result->errorCode;

        $message->error_message =
            $result->errorMessage;

        $payload =
            is_array($message->payload)
                ? $message->payload
                : [];

        $payload['provider_response'] =
            $result->rawResponse;

        $payload['provider_metadata'] =
            $result->metadata;

        $payload['send_error'] = [
            'code' =>
                $result->errorCode,

            'message' =>
                $result->errorMessage,

            'failed_at' =>
                now()->toIso8601String(),
        ];

        $message->payload =
            $payload;

        $message->save();

        $this->broadcastMessageChange(
            message: $message,
            changeType: 'status_updated'
        );
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    protected function broadcastMessageChange(
        Message $message,
        string $changeType
    ): void {
        try {
            $message->refresh();
<<<<<<< HEAD

            $message->load(
                'conversation'
            );
=======
            $message->load('conversation');
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

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
<<<<<<< HEAD
                        $exception
                            ->getMessage(),
=======
                        $exception->getMessage(),
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                ]
            );
        }
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
