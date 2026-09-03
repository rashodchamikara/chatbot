<?php

namespace App\Services\Omnichannel\Adapters;

use App\Contracts\Omnichannel\ChannelAdapter;
use App\Data\Omnichannel\InboundMessageData;
use App\Data\Omnichannel\OutboundMessageData;
use App\Data\Omnichannel\SendResult;
use App\Events\ConversationMessageCreated;
use App\Models\ChannelConnection;
use App\Models\Message;
use App\Support\Omnichannel\WebsiteIdentity;
use Illuminate\Http\Request;

class WebsiteAdapter implements ChannelAdapter
{
    public function type(): string
    {
        return 'website';
    }

    /**
     * Convert our existing website-widget request
     * into the common inbound DTO.
     */
    public function parseInbound(
        ChannelConnection $connection,
        Request $request,
    ): ?InboundMessageData {
        if (
            strtolower(
                trim(
                    (string)
                    $connection->type
                )
            ) !== 'website'
        ) {
            return null;
        }

        if (!$connection->website_id) {
            return null;
        }

        $visitorId =
            trim(
                (string)
                $request->input(
                    'visitor_id'
                )
            );

        $text =
            trim(
                (string)
                $request->input(
                    'message'
                )
            );

        if (
            $visitorId === ''
            || $text === ''
        ) {
            return null;
        }

        return new InboundMessageData(
            tenantId:
                (int)
                $connection->tenant_id,

            channelConnectionId:
                (int)
                $connection->id,

            externalContactId:
                WebsiteIdentity::externalContactId(
                    $visitorId
                ),

            externalMessageId:
                WebsiteIdentity::externalMessageId(
                    (int)
                    $connection->id,

                    $request->input(
                        'client_message_id'
                    )
                ),

            externalThreadId:
                WebsiteIdentity::externalThreadId(
                    (int)
                    $connection->website_id,
                    $visitorId
                ),

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
                'source' =>
                    'website_widget',

                'website_id' =>
                    (int)
                    $connection->website_id,

                'visitor_id' =>
                    $visitorId,

                'client_message_id' =>
                    $request->input(
                        'client_message_id'
                    ),
            ],
        );
    }

    /**
     * Website outbound delivery does not call an
     * external API.
     *
     * Delivery means broadcasting the stored message
     * through the existing Reverb website-chat event.
     */
    public function send(
        ChannelConnection $connection,
        OutboundMessageData $message,
    ): SendResult {
        if (
            strtolower(
                trim(
                    (string)
                    $connection->type
                )
            ) !== 'website'
        ) {
            return SendResult::failure(
                errorMessage:
                    'WebsiteAdapter received a non-website channel connection.',

                errorCode:
                    'invalid_channel_type',
            );
        }

        $localMessageId =
            $message->metadata[
                'message_id'
            ] ?? null;

        if (!$localMessageId) {
            return SendResult::failure(
                errorMessage:
                    'The local message ID is missing from outbound metadata.',

                errorCode:
                    'missing_local_message_id',
            );
        }

        $storedMessage =
            Message::query()
                ->with([
                    'conversation',
                    'user',
                ])
                ->find(
                    $localMessageId
                );

        if (!$storedMessage) {
            return SendResult::failure(
                errorMessage:
                    'The outbound website message could not be found.',

                errorCode:
                    'message_not_found',
            );
        }

        if (
            (int)
            $storedMessage
                ->channel_connection_id
            !==
            (int)
            $connection->id
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
                    'The outbound message has no conversation.',

                errorCode:
                    'conversation_not_found',
            );
        }

        /*
         * Preserve the exact Reverb event currently
         * consumed by the website widget/admin chat.
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

            metadata: [
                'provider' =>
                    'native',

                'delivered_locally' =>
                    true,

                'broadcast_channel' =>
                    'conversation.'
                    . $storedMessage
                        ->conversation
                        ->realtime_token,
            ],
        );
    }
}