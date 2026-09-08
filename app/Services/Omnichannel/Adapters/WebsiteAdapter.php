<?php

namespace App\Services\Omnichannel\Adapters;

use App\Contracts\Omnichannel\ChannelAdapter;
use App\Data\Omnichannel\InboundMessageData;
use App\Data\Omnichannel\OutboundMessageData;
use App\Data\Omnichannel\SendResult;
<<<<<<< HEAD
use App\Events\ConversationMessageCreated;
use App\Models\ChannelConnection;
use App\Models\Message;
use App\Support\Omnichannel\WebsiteIdentity;
=======
use App\Enums\ChannelType;
use App\Events\ConversationMessageCreated;
use App\Models\ChannelConnection;
use App\Models\Message;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Http\Request;

class WebsiteAdapter implements ChannelAdapter
{
    public function type(): string
    {
<<<<<<< HEAD
        return 'website';
    }

    /**
     * Convert our existing website-widget request
     * into the common inbound DTO.
     */
=======
        return ChannelType::Website->value;
    }

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function parseInbound(
        ChannelConnection $connection,
        Request $request,
    ): ?InboundMessageData {
<<<<<<< HEAD
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
=======
        $visitorId = trim(
            (string) $request->input('visitor_id', '')
        );

        $text = trim(
            (string) $request->input('message', '')
        );

        if ($visitorId === '' || $text === '') {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            return null;
        }

        return new InboundMessageData(
<<<<<<< HEAD
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
=======
            tenantId: (int) $connection->tenant_id,
            channelConnectionId: (int) $connection->id,
            externalContactId: $visitorId,
            externalThreadId: $visitorId,
            externalMessageId: null,
            messageType: 'text',
            text: $text,
            attachments: [],
            metadata: [
                'channel' => ChannelType::Website->value,
                'provider' => $connection->provider ?: 'native',
                'website_id' => $connection->website_id,
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            ],
        );
    }

<<<<<<< HEAD
    /**
     * Website outbound delivery does not call an
     * external API.
     *
     * Delivery means broadcasting the stored message
     * through the existing Reverb website-chat event.
     */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function send(
        ChannelConnection $connection,
        OutboundMessageData $message,
    ): SendResult {
<<<<<<< HEAD
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
=======
        $localMessageId =
            $message->metadata['message_id']
            ?? null;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        if (!$localMessageId) {
            return SendResult::failure(
                errorMessage:
<<<<<<< HEAD
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
=======
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
            ->find($localMessageId);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        if (!$storedMessage) {
            return SendResult::failure(
                errorMessage:
<<<<<<< HEAD
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
=======
                    'Website outbound message could not be found.',
                errorCode:
                    'website_message_not_found',
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            );
        }

        /*
<<<<<<< HEAD
         * Preserve the exact Reverb event currently
         * consumed by the website widget/admin chat.
=======
         * Preserve the existing website Reverb event so the widget
         * continues to receive AI/agent messages exactly as before.
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
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

<<<<<<< HEAD
=======
            status:
                'sent',

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            metadata: [
                'provider' =>
                    'native',

                'delivered_locally' =>
                    true,

<<<<<<< HEAD
                'broadcast_channel' =>
                    'conversation.'
                    . $storedMessage
                        ->conversation
                        ->realtime_token,
            ],
        );
    }
}
=======
                'website_id' =>
                    $connection->website_id,

                'local_message_id' =>
                    $storedMessage->id,
            ],
        );
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
