<?php

namespace App\Services\Omnichannel\Adapters;

use App\Contracts\Omnichannel\ChannelAdapter;
use App\Data\Omnichannel\InboundMessageData;
use App\Data\Omnichannel\OutboundMessageData;
use App\Data\Omnichannel\SendResult;
use App\Exceptions\Omnichannel\WhatsAppCloudApiException;
use App\Models\ChannelConnection;
use App\Services\Omnichannel\WhatsApp\WhatsAppCloudApiClient;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppAdapter implements ChannelAdapter
{
    public function __construct(
        private readonly WhatsAppCloudApiClient $client,
        private readonly WhatsAppConnectionConfigService $configService,
    ) {
    }

    public function type(): string
    {
        return 'whatsapp';
    }

    /**
     * ChannelAdapter compatibility method.
     *
     * A Meta webhook may contain multiple messages, so the actual webhook job
     * uses parseInboundPayload(). This method returns the first parsed message
     * when called directly.
     */
    public function parseInbound(
        ChannelConnection $connection,
        Request $request,
    ): ?InboundMessageData {
        $payload = $request->all();

        if (!is_array($payload)) {
            return null;
        }

        $messages = $this->parseInboundPayload(
            connection: $connection,
            payload: $payload,
        );

        return $messages[0] ?? null;
    }

    /**
     * Parse one Meta WhatsApp webhook payload into common omnichannel DTOs.
     *
     * @return array<int, InboundMessageData>
     */
    public function parseInboundPayload(
        ChannelConnection $connection,
        array $payload,
    ): array {
        if (
            strtolower(trim((string) $connection->type)) !== 'whatsapp'
            || strtolower(trim((string) $connection->provider)) !== 'meta'
        ) {
            return [];
        }

        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return [];
        }

        $result = [];
        $configuredWabaId = trim((string) $connection->external_account_id);
        $configuredPhoneNumberId = trim((string) $connection->external_sender_id);

        foreach (($payload['entry'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $wabaId = trim((string) ($entry['id'] ?? ''));

            if (
                $configuredWabaId !== ''
                && $wabaId !== ''
                && $configuredWabaId !== $wabaId
            ) {
                continue;
            }

            foreach (($entry['changes'] ?? []) as $change) {
                if (!is_array($change) || ($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? null;

                if (!is_array($value)) {
                    continue;
                }

                $phoneNumberId = trim(
                    (string) ($value['metadata']['phone_number_id'] ?? '')
                );

                if (
                    $configuredPhoneNumberId !== ''
                    && $phoneNumberId !== ''
                    && $configuredPhoneNumberId !== $phoneNumberId
                ) {
                    continue;
                }

                $contactsByWaId = $this->contactsByWaId(
                    is_array($value['contacts'] ?? null)
                        ? $value['contacts']
                        : []
                );

                $messages = $value['messages'] ?? [];

                if (!is_array($messages)) {
                    continue;
                }

                foreach ($messages as $providerMessage) {
                    if (!is_array($providerMessage)) {
                        continue;
                    }

                    $messageData = $this->normalizeProviderMessage(
                        connection: $connection,
                        wabaId: $wabaId,
                        phoneNumberId: $phoneNumberId,
                        providerMessage: $providerMessage,
                        contactsByWaId: $contactsByWaId,
                    );

                    if ($messageData !== null) {
                        $result[] = $messageData;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Send an outbound WhatsApp text message.
     */
    public function send(
        ChannelConnection $connection,
        OutboundMessageData $message,
    ): SendResult {
        if (strtolower(trim((string) $connection->type)) !== 'whatsapp') {
            return SendResult::failure(
                errorMessage: 'WhatsAppAdapter received a non-whatsapp channel connection.',
                errorCode: 'invalid_channel_type',
            );
        }

        if (strtolower(trim((string) $connection->provider)) !== 'meta') {
            return SendResult::failure(
                errorMessage: 'WhatsApp connection provider must be meta.',
                errorCode: 'invalid_whatsapp_provider',
            );
        }

        if (strtolower(trim((string) $connection->status)) !== 'active') {
            return SendResult::failure(
                errorMessage: 'WhatsApp connection is not active.',
                errorCode: 'whatsapp_connection_inactive',
            );
        }

        if (strtolower(trim((string) $message->messageType)) !== 'text') {
            return SendResult::failure(
                errorMessage: 'WhatsAppAdapter currently supports outbound text messages only.',
                errorCode: 'unsupported_message_type',
            );
        }

        if (!empty($message->attachments)) {
            return SendResult::failure(
                errorMessage: 'WhatsApp attachments are not supported in this sprint.',
                errorCode: 'attachments_not_supported',
            );
        }

        $recipient = $this->normalizeRecipient(
            $message->externalContactId
        );

        if ($recipient === null) {
            return SendResult::failure(
                errorMessage: 'WhatsApp recipient number is invalid.',
                errorCode: 'invalid_recipient',
            );
        }

        $body = trim((string) $message->text);

        if ($body === '') {
            return SendResult::failure(
                errorMessage: 'WhatsApp message body cannot be empty.',
                errorCode: 'empty_message_body',
            );
        }

        try {
            $this->configService->assertConfigurationReady($connection);

            $accessToken = $this->configService->accessToken($connection);

            $response = $this->client->sendTextMessage(
                phoneNumberId: (string) $connection->external_sender_id,
                accessToken: $accessToken,
                recipient: $recipient,
                body: $body,
                previewUrl: (bool) ($message->metadata['preview_url'] ?? false),
                replyToMessageId: $message->replyToExternalId,
            );

            $externalMessageId = $this->extractMessageId($response);

            if ($externalMessageId === null) {
                return SendResult::failure(
                    errorMessage: 'Meta accepted the request but did not return a WhatsApp message ID.',
                    errorCode: 'missing_meta_message_id',
                );
            }

            return SendResult::success(
                externalMessageId: $externalMessageId,
                status: 'sent',
                metadata: [
                    'provider' => 'meta',
                    'channel' => 'whatsapp',
                    'recipient' => $recipient,
                    'response' => $response,
                ],
            );
        } catch (WhatsAppCloudApiException $exception) {
            return SendResult::failure(
                errorMessage: $exception->getMessage(),
                errorCode: $exception->metaErrorCode ?? 'meta_api_error',
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unexpected WhatsApp outbound send failure.',
                [
                    'connection_id' => $connection->id,
                    'conversation_id' => $message->conversationId,
                    'error' => $exception->getMessage(),
                ]
            );

            return SendResult::failure(
                errorMessage: $exception->getMessage(),
                errorCode: 'whatsapp_send_error',
            );
        }
    }

    /**
     * @param array<string, array<string, mixed>> $contactsByWaId
     */
    private function normalizeProviderMessage(
        ChannelConnection $connection,
        string $wabaId,
        string $phoneNumberId,
        array $providerMessage,
        array $contactsByWaId,
    ): ?InboundMessageData {
        $from = $this->normalizeRecipient(
            isset($providerMessage['from'])
                ? (string) $providerMessage['from']
                : null
        );

        $externalMessageId = trim(
            (string) ($providerMessage['id'] ?? '')
        );

        if ($from === null || $externalMessageId === '') {
            return null;
        }

        $type = strtolower(
            trim((string) ($providerMessage['type'] ?? 'text'))
        );

        if ($type === '') {
            $type = 'text';
        }

        $contact = $contactsByWaId[$from] ?? [];

        $contactName = trim(
            (string) ($contact['profile']['name'] ?? '')
        );

        $timestamp = trim(
            (string) ($providerMessage['timestamp'] ?? '')
        );

        $contextMessageId = trim(
            (string) ($providerMessage['context']['id'] ?? '')
        );

        [$text, $attachments, $contentMetadata] =
            $this->extractInboundContent(
                type: $type,
                providerMessage: $providerMessage,
            );

        return new InboundMessageData(
            tenantId: (int) $connection->tenant_id,
            channelConnectionId: (int) $connection->id,
            externalContactId: $from,
            externalMessageId: $externalMessageId,
            externalThreadId: $from,
            contactName: $contactName !== '' ? $contactName : null,
            contactEmail: null,
            contactPhone: $from,
            messageType: $type,
            text: $text,
            attachments: $attachments,
            metadata: array_merge(
                [
                    'channel' => 'whatsapp',
                    'provider' => 'meta',
                    'waba_id' => $wabaId !== '' ? $wabaId : null,
                    'phone_number_id' => $phoneNumberId !== '' ? $phoneNumberId : null,
                    'wa_id' => $from,
                    'provider_timestamp' => $timestamp !== '' ? $timestamp : null,
                    'context_message_id' => $contextMessageId !== ''
                        ? $contextMessageId
                        : null,
                ],
                $contentMetadata,
            ),
        );
    }

    /**
     * @return array{0:?string,1:array<int,array<string,mixed>>,2:array<string,mixed>}
     */
    private function extractInboundContent(
        string $type,
        array $providerMessage,
    ): array {
        $attachments = [];
        $metadata = [];

        switch ($type) {
            case 'text':
                return [
                    trim((string) ($providerMessage['text']['body'] ?? '')),
                    [],
                    [],
                ];

            case 'button':
                return [
                    trim((string) ($providerMessage['button']['text'] ?? '')),
                    [],
                    [
                        'button_payload' => $providerMessage['button']['payload'] ?? null,
                    ],
                ];

            case 'interactive':
                $interactive = is_array($providerMessage['interactive'] ?? null)
                    ? $providerMessage['interactive']
                    : [];

                $interactiveType = trim((string) ($interactive['type'] ?? ''));
                $reply = [];

                if ($interactiveType === 'button_reply') {
                    $reply = is_array($interactive['button_reply'] ?? null)
                        ? $interactive['button_reply']
                        : [];
                } elseif ($interactiveType === 'list_reply') {
                    $reply = is_array($interactive['list_reply'] ?? null)
                        ? $interactive['list_reply']
                        : [];
                }

                return [
                    trim((string) ($reply['title'] ?? $reply['description'] ?? '[Interactive reply]')),
                    [],
                    [
                        'interactive_type' => $interactiveType !== '' ? $interactiveType : null,
                        'interactive_reply_id' => $reply['id'] ?? null,
                    ],
                ];

            case 'image':
            case 'video':
            case 'audio':
            case 'document':
            case 'sticker':
                $media = is_array($providerMessage[$type] ?? null)
                    ? $providerMessage[$type]
                    : [];

                $mediaId = trim((string) ($media['id'] ?? ''));
                $caption = trim((string) ($media['caption'] ?? ''));

                if ($mediaId !== '') {
                    $attachments[] = [
                        'external_attachment_id' => $mediaId,
                        'type' => $type,
                        'mime_type' => $media['mime_type'] ?? null,
                        'original_name' => $media['filename'] ?? null,
                        'metadata' => [
                            'sha256' => $media['sha256'] ?? null,
                        ],
                    ];
                }

                return [
                    $caption !== ''
                        ? $caption
                        : sprintf('[WhatsApp %s received]', $type),
                    $attachments,
                    [],
                ];

            case 'location':
                $location = is_array($providerMessage['location'] ?? null)
                    ? $providerMessage['location']
                    : [];

                $name = trim((string) ($location['name'] ?? ''));
                $address = trim((string) ($location['address'] ?? ''));
                $latitude = $location['latitude'] ?? null;
                $longitude = $location['longitude'] ?? null;

                $parts = array_values(
                    array_filter(
                        [$name, $address],
                        fn ($value) => is_string($value) && trim($value) !== ''
                    )
                );

                $text = $parts !== []
                    ? implode(' - ', $parts)
                    : '[WhatsApp location received]';

                return [
                    $text,
                    [],
                    [
                        'location' => [
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'name' => $name !== '' ? $name : null,
                            'address' => $address !== '' ? $address : null,
                        ],
                    ],
                ];

            case 'contacts':
                return [
                    '[WhatsApp contact received]',
                    [],
                    [
                        'contacts' => $providerMessage['contacts'] ?? [],
                    ],
                ];

            case 'reaction':
                return [
                    trim((string) ($providerMessage['reaction']['emoji'] ?? '[Reaction]')),
                    [],
                    [
                        'reaction_message_id' => $providerMessage['reaction']['message_id'] ?? null,
                    ],
                ];

            case 'unsupported':
                return [
                    '[Unsupported WhatsApp message received]',
                    [],
                    [
                        'errors' => $providerMessage['errors'] ?? [],
                    ],
                ];

            default:
                return [
                    sprintf('[WhatsApp %s message received]', $type),
                    [],
                    [],
                ];
        }
    }

    /**
     * @param array<int, mixed> $contacts
     * @return array<string, array<string, mixed>>
     */
    private function contactsByWaId(array $contacts): array
    {
        $result = [];

        foreach ($contacts as $contact) {
            if (!is_array($contact)) {
                continue;
            }

            $waId = $this->normalizeRecipient(
                isset($contact['wa_id'])
                    ? (string) $contact['wa_id']
                    : null
            );

            if ($waId !== null) {
                $result[$waId] = $contact;
            }
        }

        return $result;
    }

    private function normalizeRecipient(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $number = preg_replace('/\D+/', '', $value) ?? '';

        if ($number === '') {
            return null;
        }

        if (strlen($number) < 8 || strlen($number) > 15) {
            return null;
        }

        return $number;
    }

    private function extractMessageId(array $response): ?string
    {
        $messages = $response['messages'] ?? null;

        if (
            !is_array($messages)
            || !isset($messages[0])
            || !is_array($messages[0])
        ) {
            return null;
        }

        $id = trim((string) ($messages[0]['id'] ?? ''));

        return $id !== '' ? $id : null;
    }
}
