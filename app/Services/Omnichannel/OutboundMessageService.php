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

        $conversation->loadMissing('channelConnection');

        $connection = $conversation->channelConnection;

        if (!$connection) {
            throw new RuntimeException(
                'Channel connection could not be found.'
            );
        }

        if (
            (int) $conversation->tenant_id
            !== (int) $connection->tenant_id
        ) {
            throw new RuntimeException(
                'Conversation and channel connection belong to different tenants.'
            );
        }

        $identity = ContactIdentity::query()
            ->where('tenant_id', $conversation->tenant_id)
            ->where('contact_id', $conversation->contact_id)
            ->where('channel_connection_id', $connection->id)
            ->first();

        if (!$identity) {
            throw new RuntimeException(
                'No contact identity exists for this conversation and channel.'
            );
        }

        $externalContactId = trim(
            (string) $identity->external_user_id
        );

        if ($externalContactId === '') {
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

                $message->conversation_id = $conversation->id;
                $message->channel_connection_id = $connection->id;
                $message->direction = 'outbound';
                $message->sender_type = $senderType;
                $message->message_type = empty($attachments)
                    ? 'text'
                    : 'attachment';
                $message->message = $body;
                $message->is_ai_generated = $isAiGenerated;
                $message->status = 'pending';
                /*
                |--------------------------------------------------------------------------
                | Legacy website-chat compatibility
                |--------------------------------------------------------------------------
                |
                | Keep populating the original message columns while the existing
                | website widget/admin UI still coexists with the omnichannel schema.
                |
                */

                $message->sender =
                    $isAiGenerated
                        ? 'assistant'
                        : 'agent';

                $message->role =
                    'assistant';

                $message->tokens_used =
                    0;

                $message->is_system =
                    false;
                $message->provider_status = 'pending';

                if ($senderUserId !== null) {
                    $message->sender_user_id = $senderUserId;
                }

                if ($replyToExternalId !== null) {
                    $message->external_reply_to_id = $replyToExternalId;
                }

                $message->payload = [
                    'attachments' => $attachments,
                    'metadata' => $metadata,
                ];

                $message->save();

                return $message;
            }
        );

        $this->broadcastMessageChange(
            message: $message,
            changeType: 'created'
        );

        $outbound = new OutboundMessageData(
            tenantId: (int) $conversation->tenant_id,
            conversationId: (int) $conversation->id,
            channelConnectionId: (int) $connection->id,
            externalContactId: $externalContactId,
            externalThreadId: $conversation->external_thread_id,
            messageType: $message->message_type,
            text: $body,
            attachments: $attachments,
            metadata: array_merge(
                $metadata,
                [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ]
            ),
            replyToExternalId: $replyToExternalId,
        );

        try {
            $adapter = $this->channelManager
                ->forConnection($connection);

            $result = $adapter->send(
                $connection,
                $outbound
            );
        } catch (Throwable $exception) {
            $this->markFailedFromException(
                message: $message,
                exception: $exception
            );

            throw $exception;
        }

        if (!$result instanceof SendResult) {
            $exception = new RuntimeException(
                'Channel adapter returned an invalid send result.'
            );

            $this->markFailedFromException(
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
                $status = trim($result->status) !== ''
                    ? $result->status
                    : 'sent';

                $message->external_message_id =
                    $result->externalMessageId;

                $message->status = $status;
                $message->provider_status = $status;
                $message->error_code = null;
                $message->error_message = null;

                if (
                    in_array(
                        $status,
                        ['sent', 'delivered', 'read'],
                        true
                    )
                    && !$message->sent_at
                ) {
                    $message->sent_at = now();
                }

                if (
                    $status === 'delivered'
                    && !$message->delivered_at
                ) {
                    $message->delivered_at = now();
                }

                if (
                    $status === 'read'
                    && !$message->read_at
                ) {
                    $message->read_at = now();
                }

                $payload = is_array($message->payload)
                    ? $message->payload
                    : [];

                $payload['provider_response'] =
                    $result->rawResponse;

                $payload['provider_metadata'] =
                    $result->metadata;

                $message->payload = $payload;
                $message->save();

                $conversation->last_message_at = now();

                if (!$conversation->first_response_at) {
                    $conversation->first_response_at = now();
                }

                $conversation->save();
            }
        );

        $this->broadcastMessageChange(
            message: $message,
            changeType: 'status_updated'
        );

        return $message->fresh();
    }

    protected function markFailedFromException(
        Message $message,
        Throwable $exception
    ): void {
        $message->status = 'failed';
        $message->provider_status = 'failed';
        $message->error_message = $exception->getMessage();

        $payload = is_array($message->payload)
            ? $message->payload
            : [];

        $payload['send_error'] = [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
            'failed_at' => now()->toIso8601String(),
        ];

        $message->payload = $payload;
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
        $message->status = 'failed';
        $message->provider_status = $result->status ?: 'failed';
        $message->error_code = $result->errorCode;
        $message->error_message = $result->errorMessage;

        $payload = is_array($message->payload)
            ? $message->payload
            : [];

        $payload['provider_response'] =
            $result->rawResponse;

        $payload['provider_metadata'] =
            $result->metadata;

        $payload['send_error'] = [
            'code' => $result->errorCode,
            'message' => $result->errorMessage,
            'failed_at' => now()->toIso8601String(),
        ];

        $message->payload = $payload;
        $message->save();

        $this->broadcastMessageChange(
            message: $message,
            changeType: 'status_updated'
        );
    }

    protected function broadcastMessageChange(
        Message $message,
        string $changeType
    ): void {
        try {
            $message->refresh();
            $message->load('conversation');

            OmnichannelMessageChanged::dispatch(
                $message,
                $changeType
            );
        } catch (Throwable $exception) {
            Log::warning(
                'Omnichannel realtime broadcast failed.',
                [
                    'message_id' => $message->id,
                    'change_type' => $changeType,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }
}
