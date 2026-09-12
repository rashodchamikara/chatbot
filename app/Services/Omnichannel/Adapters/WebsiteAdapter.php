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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebsiteAdapter implements ChannelAdapter
{
    public function type(): string
    {
        return ChannelType::Website->value;
    }

    /**
     * Convert a website-widget request into the common
     * omnichannel inbound DTO.
     *
     * Expected widget payload:
     *
     * {
     *     "visitor_id": "visitor_xxx",
     *     "message": "Hello"
     * }
     *
     * client_message_id is optional. If the widget does not
     * provide one, we generate a server-side UUID so inbound
     * messages never share a NULL external_message_id.
     */
    public function parseInbound(
        ChannelConnection $connection,
        Request $request,
    ): ?InboundMessageData {
        /*
         * This adapter only handles native website traffic.
         * WhatsApp webhook parsing belongs in WhatsAppAdapter.
         */
        if (
            strtolower(
                trim((string) $connection->type)
            ) !== ChannelType::Website->value
        ) {
            Log::warning(
                'WebsiteAdapter rejected non-website connection.',
                [
                    'connection_id' =>
                        $connection->id,

                    'connection_type' =>
                        $connection->type,
                ]
            );

            return null;
        }

        if (!$connection->website_id) {
            Log::warning(
                'WebsiteAdapter connection has no website_id.',
                [
                    'connection_id' =>
                        $connection->id,

                    'tenant_id' =>
                        $connection->tenant_id,
                ]
            );

            return null;
        }

        /*
         * Request::input() handles JSON, form-data and
         * form-urlencoded input consistently.
         */
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
            ||
            $text === ''
        ) {
            /*
             * Do not log actual visitor/message values.
             * Only log enough metadata to diagnose payload shape.
             */
            Log::warning(
                'WebsiteAdapter could not normalize inbound message.',
                [
                    'connection_id' =>
                        $connection->id,

                    'tenant_id' =>
                        $connection->tenant_id,

                    'website_id' =>
                        $connection->website_id,

                    'request_keys' =>
                        array_keys(
                            $request->all()
                        ),

                    'visitor_id_present' =>
                        $visitorId !== '',

                    'message_present' =>
                        $text !== '',
                ]
            );

            return null;
        }

        /*
         * Your current widget sends:
         *
         * {
         *     visitor_id: "...",
         *     message: "..."
         * }
         *
         * It currently does not provide client_message_id.
         *
         * If it is added later, use it for deterministic
         * request-level deduplication. Otherwise generate a
         * UUID so external_message_id is never NULL.
         */
        $clientMessageId = trim(
            (string) $request->input(
                'client_message_id',
                ''
            )
        );

        if ($clientMessageId !== '') {
            $externalMessageId =
                'website:'
                . $connection->id
                . ':'
                . $clientMessageId;
        } else {
            $externalMessageId =
                'website:'
                . $connection->id
                . ':'
                . Str::uuid()->toString();
        }

        /*
         * Keep the existing visitor ID as the external
         * contact/thread identity.
         *
         * This keeps the new omnichannel flow compatible
         * with the existing website visitor IDs.
         */
        return new InboundMessageData(
            tenantId:
                (int) $connection->tenant_id,

            channelConnectionId:
                (int) $connection->id,

            externalContactId:
                $visitorId,

            externalMessageId:
                $externalMessageId,

            externalThreadId:
                $visitorId,

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

                'source' =>
                    'website_widget',

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