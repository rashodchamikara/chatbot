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
    Request $request,
): ?InboundMessageData {
    $messages = $this->parseInboundPayload(
        connection: $connection,
        payload: $request->json()->all(),
    );

    return $messages[0] ?? null;
}

    /**
     * Parse all supported messages in a Meta webhook.
     *
     * Sprint 2.8.4.2 handles inbound TEXT only.
     *
     * @return array<int, InboundMessageData>
     */
    public function parseInboundPayload(
        ChannelConnection $connection,
        array $payload,
    ): array {
        if (
            strtolower(
                trim((string) $connection->type)
            ) !== 'whatsapp'
        ) {
            return [];
        }

        if (
            strtolower(
                trim((string) $connection->provider)
            ) !== 'meta'
        ) {
            return [];
        }

        if (
            ($payload['object'] ?? null)
            !== 'whatsapp_business_account'
        ) {
            return [];
        }

        $connectionWabaId =
            trim(
                (string)
                $connection->external_account_id
            );

        $connectionPhoneNumberId =
            trim(
                (string)
                $connection->external_sender_id
            );

        if (
            $connectionWabaId === ''
            ||
            $connectionPhoneNumberId === ''
        ) {
            return [];
        }

        $results = [];

        $entries = $payload['entry'] ?? [];

        if (!is_array($entries)) {
            return [];
        }

        foreach ($entries as $entry) {

            if (!is_array($entry)) {
                continue;
            }

            /*
            * Meta entry.id is the WABA ID.
            */
            $wabaId = trim(
                (string) (
                    $entry['id']
                    ?? ''
                )
            );

            if (
                $wabaId === ''
                ||
                $wabaId !== $connectionWabaId
            ) {
                continue;
            }

            $changes =
                $entry['changes']
                ?? [];

            if (!is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {

                if (!is_array($change)) {
                    continue;
                }

                /*
                * Incoming WhatsApp messages use
                * the "messages" webhook field.
                */
                if (
                    ($change['field'] ?? null)
                    !== 'messages'
                ) {
                    continue;
                }

                $value =
                    $change['value']
                    ?? null;

                if (!is_array($value)) {
                    continue;
                }

                if (
                    isset(
                        $value['messaging_product']
                    )
                    &&
                    $value['messaging_product']
                    !== 'whatsapp'
                ) {
                    continue;
                }

                $providerMetadata =
                    $value['metadata']
                    ?? [];

                if (
                    !is_array(
                        $providerMetadata
                    )
                ) {
                    $providerMetadata = [];
                }

                $phoneNumberId =
                    trim(
                        (string) (
                            $providerMetadata[
                                'phone_number_id'
                            ]
                            ?? ''
                        )
                    );

                /*
                * Critical multi-tenant security check.
                */
                if (
                    $phoneNumberId === ''
                    ||
                    $phoneNumberId !==
                        $connectionPhoneNumberId
                ) {
                    continue;
                }

                $contacts =
                    $value['contacts']
                    ?? [];

                if (!is_array($contacts)) {
                    $contacts = [];
                }

                $messages =
                    $value['messages']
                    ?? [];

                /*
                * Delivery/read events contain statuses
                * instead of messages.
                *
                * We handle those in a later sprint.
                */
                if (!is_array($messages)) {
                    continue;
                }

                foreach ($messages as $message) {

                    if (!is_array($message)) {
                        continue;
                    }

                    $type =
                        strtolower(
                            trim(
                                (string) (
                                    $message['type']
                                    ?? ''
                                )
                            )
                        );

                    /*
                    * Text only for this sprint.
                    */
                    if ($type !== 'text') {
                        continue;
                    }

                    $messageId =
                        trim(
                            (string) (
                                $message['id']
                                ?? ''
                            )
                        );

                    $sender =
                        trim(
                            (string) (
                                $message['from']
                                ?? ''
                            )
                        );

                    $body =
                        trim(
                            (string) (
                                $message[
                                    'text'
                                ][
                                    'body'
                                ]
                                ?? ''
                            )
                        );

                    if (
                        $messageId === ''
                        ||
                        $sender === ''
                        ||
                        $body === ''
                    ) {
                        continue;
                    }

                    $contact =
                        $this->findWhatsAppContact(
                            contacts: $contacts,
                            sender: $sender,
                        );

                    $waId =
                        trim(
                            (string) (
                                $contact['wa_id']
                                ?? $sender
                            )
                        );

                    if ($waId === '') {
                        $waId = $sender;
                    }

                    $contactName =
                        $this->extractWhatsAppContactName(
                            $contact
                        );

                    $replyToExternalId =
                        trim(
                            (string) (
                                $message[
                                    'context'
                                ][
                                    'id'
                                ]
                                ?? ''
                            )
                        );

                    /*
                    * WhatsApp 1:1 conversations don't
                    * expose an independent thread ID.
                    *
                    * wa_id is stable for the contact and
                    * business relationship, so we use it
                    * as our external thread ID.
                    */
                    $results[] =
                        new InboundMessageData(
                            tenantId:
                                (int)
                                $connection->tenant_id,

                            channelConnectionId:
                                (int)
                                $connection->id,

                            externalContactId:
                                $waId,

                            externalMessageId:
                                $messageId,

                            externalThreadId:
                                $waId,

                            contactName:
                                $contactName,

                            contactEmail:
                                null,

                            contactPhone:
                                $sender,

                            messageType:
                                'text',

                            text:
                                $body,

                            attachments:
                                [],

                            metadata: [
                                'channel' =>
                                    'whatsapp',

                                'provider' =>
                                    'meta',

                                'waba_id' =>
                                    $wabaId,

                                'phone_number_id' =>
                                    $phoneNumberId,

                                'display_phone_number' =>
                                    $providerMetadata[
                                        'display_phone_number'
                                    ] ?? null,

                                'sender_wa_id' =>
                                    $waId,

                                'whatsapp_message_id' =>
                                    $messageId,

                                'timestamp' =>
                                    $message[
                                        'timestamp'
                                    ] ?? null,

                                'reply_to_external_message_id' =>
                                    $replyToExternalId !== ''
                                        ? $replyToExternalId
                                        : null,
                            ],
                        );
                }
            }
        }

        return $results;
    }

    private function findWhatsAppContact(
        array $contacts,
        string $sender,
    ): array {
        foreach ($contacts as $contact) {

            if (!is_array($contact)) {
                continue;
            }

            $waId =
                trim(
                    (string) (
                        $contact['wa_id']
                        ?? ''
                    )
                );

            if (
                $waId !== ''
                &&
                $waId === $sender
            ) {
                return $contact;
            }
        }

        if (
            isset($contacts[0])
            &&
            is_array(
                $contacts[0]
            )
        ) {
            return $contacts[0];
        }

        return [];
    }

    private function extractWhatsAppContactName(
        array $contact
    ): ?string {
        $profile =
            $contact['profile']
            ?? null;

        if (!is_array($profile)) {
            return null;
        }

        $name =
            trim(
                (string) (
                    $profile['name']
                    ?? ''
                )
            );

        return $name !== ''
            ? $name
            : null;
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