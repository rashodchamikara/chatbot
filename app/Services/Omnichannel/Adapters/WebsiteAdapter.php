<?php

namespace App\Services\Omnichannel\Adapters;

use App\Contracts\Omnichannel\ChannelAdapter;
use App\Data\Omnichannel\InboundMessageData;
use App\Data\Omnichannel\OutboundMessageData;
use App\Data\Omnichannel\SendResult;
use App\Enums\ChannelType;
use App\Events\ConversationMessageCreated;
use App\Models\ChannelConnection;
use App\Models\Message;
use Illuminate\Http\Request;

class WebsiteAdapter implements ChannelAdapter
{
    public function type(): string
    {
        return ChannelType::Website->value;
    }

    /**
     * Convert a website-widget request into the common
     * omnichannel inbound DTO.
     */
    public function parseInbound(
        ChannelConnection $connection,
        Request $request
    ): ?InboundMessageData {
        if (
            strtolower(
                trim(
                    (string) $connection->type
                )
            ) !== ChannelType::Website->value
        ) {
            return null;
        }

        if (!$connection->website_id) {
            return null;
        }

        $visitorId = trim(
            (string) $request->input(
                'visitor_id',
                ''
            )
        );

        $text = trim(
            (string) $request->input(
                'message',
                ''
            )
        );

        if (
            $visitorId === ''
            || $text === ''
        ) {
            return null;
        }

        /*
         * If the widget provides a client message ID,
         * convert it into a channel-scoped provider ID.
         *
         * This allows InboundMessageService to prevent
         * accidental duplicate messages.
         */
        $clientMessageId = trim(
            (string) $request->input(
                'client_message_id',
                ''
            )
        );

        $externalMessageId = null;

        if ($clientMessageId !== '') {
            $externalMessageId =
                'website:'
                . $connection->id
                . ':'
                . $clientMessageId;
        }

        return new InboundMessageData(
            tenantId:
                (int) $connection->tenant_id,

            channelConnectionId:
                (int) $connection->id,

            externalContactId:
                $visitorId,

            externalThreadId:
                $visitorId,

            externalMessageId:
                $externalMessageId,

            contactName:
                null,

            contactEmail:
                null,

            contactPhone:
                null,

            messageType:
                'text',

            text:
                $text,

            attachments:
                [],

            metadata: [
                'channel' =>
                    ChannelType::Website->value,

                'provider' =>
                    $connection->provider
                    ?: 'native',

                'website_id' =>
                    (int) $connection->website_id,

                'visitor_id' =>
                    $visitorId,

                'client_message_id' =>
                    $clientMessageId !== ''
                        ? $clientMessageId
                        : null,
            ],
        );
    }

    /**
     * Website outbound delivery does not require an
     * external provider API.
     *
     * The existing Reverb event remains responsible for
     * delivering the message to the website widget.
     */
    public function send(
        ChannelConnection $connection,
        OutboundMessageData $message
    ): SendResult {
        if (
            strtolower(
                trim(
                    (string) $connection->type
                )
            ) !== ChannelType::Website->value
        ) {
            return SendResult::failure(
                errorMessage:
                    'WebsiteAdapter received a non-website channel connection.',

                errorCode:
                    'invalid_channel_type',
            );
        }

        $localMessageId =
            $message->metadata['message_id']
            ?? null;

        if (!$localMessageId) {
            return SendResult::failure(
                errorMessage:
                    'Website outbound message is missing its local message ID.',

                errorCode:
                    'website_missing_local_message_id',
            );
        }

        $storedMessage = Message::query()
            ->with([
                'conversation.website',
                'user',
            ])
            ->find(
                $localMessageId
            );

        if (!$storedMessage) {
            return SendResult::failure(
                errorMessage:
                    'Website outbound message could not be found.',

                errorCode:
                    'website_message_not_found',
            );
        }

        if (
            (int) $storedMessage->channel_connection_id
            !==
            (int) $connection->id
        ) {
            return SendResult::failure(
                errorMessage:
                    'Message and channel connection do not match.',

                errorCode:
                    'connection_mismatch',
            );
        }

        if (!$storedMessage->conversation) {
            return SendResult::failure(
                errorMessage:
                    'The outbound website message has no conversation.',

                errorCode:
                    'conversation_not_found',
            );
        }

        /*
         * Preserve the existing website Reverb event
         * consumed by the widget/live-chat interface.
         */
        broadcast(
            new ConversationMessageCreated(
                $storedMessage
            )
        );

        return SendResult::success(
            externalMessageId:
                'website-message:'
                . $storedMessage->id,

            status:
                'sent',

            metadata: [
                'provider' =>
                    'native',

                'delivered_locally' =>
                    true,

                'website_id' =>
                    $connection->website_id,

                'local_message_id' =>
                    $storedMessage->id,

                'broadcast_channel' =>
                    'conversation.'
                    . $storedMessage
                        ->conversation
                        ->realtime_token,
            ],
        );
    }
}