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
     * Inbound parsing will be implemented in
     * Sprint 2.8.4.2.
     *
     * We must still implement the method because
     * ChannelAdapter requires it.
     */
    public function parseInbound(
        ChannelConnection $connection,
        Request $request,
    ): ?InboundMessageData {
        return null;
    }

    /**
     * Send an outbound WhatsApp message.
     */
    public function send(
        ChannelConnection $connection,
        OutboundMessageData $message,
    ): SendResult {
        /*
         * Guard against incorrect ChannelManager
         * routing.
         */
        if (
            strtolower(
                trim(
                    (string) $connection->type
                )
            ) !== 'whatsapp'
        ) {
            return SendResult::failure(
                errorMessage:
                    'WhatsAppAdapter received a non-whatsapp channel connection.',

                errorCode:
                    'invalid_channel_type',
            );
        }

        /*
         * This implementation is specifically for
         * Meta WhatsApp Cloud API.
         */
        if (
            strtolower(
                trim(
                    (string) $connection->provider
                )
            ) !== 'meta'
        ) {
            return SendResult::failure(
                errorMessage:
                    'WhatsApp connection provider must be meta.',

                errorCode:
                    'invalid_whatsapp_provider',
            );
        }

        /*
         * Do not send through an unverified
         * connection.
         */
        if (
            strtolower(
                trim(
                    (string) $connection->status
                )
            ) !== 'active'
        ) {
            return SendResult::failure(
                errorMessage:
                    'WhatsApp connection is not active.',

                errorCode:
                    'whatsapp_connection_inactive',
            );
        }

        /*
         * Sprint 2.8.4.1 handles text only.
         *
         * Media support comes after the core
         * send/receive path is stable.
         */
        if (
            strtolower(
                trim(
                    (string) $message->type
                )
            ) !== 'text'
        ) {
            return SendResult::failure(
                errorMessage:
                    'WhatsAppAdapter currently supports text messages only.',

                errorCode:
                    'unsupported_message_type',
            );
        }

        if (
            !empty(
                $message->attachments
            )
        ) {
            return SendResult::failure(
                errorMessage:
                    'WhatsApp attachments are not supported in this sprint.',

                errorCode:
                    'attachments_not_supported',
            );
        }

        $recipient =
            $this->normalizeRecipient(
                $message->externalUserId
            );

        if ($recipient === null) {
            return SendResult::failure(
                errorMessage:
                    'WhatsApp recipient number is invalid.',

                errorCode:
                    'invalid_recipient',
            );
        }

        $body =
            trim(
                (string) $message->body
            );

        if ($body === '') {
            return SendResult::failure(
                errorMessage:
                    'WhatsApp message body cannot be empty.',

                errorCode:
                    'empty_message_body',
            );
        }

        try {
            /*
             * Ensures WABA, Phone Number ID and
             * encrypted access token are available.
             */
            $this->configService
                ->assertConfigurationReady(
                    $connection
                );

            $accessToken =
                $this->configService
                    ->accessToken(
                        $connection
                    );

            $response =
                $this->client
                    ->sendTextMessage(
                        phoneNumberId:
                            (string)
                            $connection
                                ->external_sender_id,

                        accessToken:
                            $accessToken,

                        recipient:
                            $recipient,

                        body:
                            $body,

                        previewUrl:
                            (bool) (
                                $message->metadata[
                                    'preview_url'
                                ] ?? false
                            ),

                        replyToMessageId:
                            $message
                                ->replyToExternalId,
                    );

            $externalMessageId =
                $this->extractMessageId(
                    $response
                );

            if ($externalMessageId === null) {
                return SendResult::failure(
                    errorMessage:
                        'Meta accepted the request but did not return a WhatsApp message ID.',

                    errorCode:
                        'missing_meta_message_id',
                );
            }

            return SendResult::success(
                externalMessageId:
                    $externalMessageId,

                status:
                    'sent',

                metadata: [
                    'provider' =>
                        'meta',

                    'channel' =>
                        'whatsapp',

                    'recipient' =>
                        $recipient,

                    /*
                     * Safe provider response.
                     *
                     * Meta's send response contains
                     * recipient/message identifiers,
                     * not our access token.
                     */
                    'response' =>
                        $response,
                ],
            );
        } catch (
            WhatsAppCloudApiException $exception
        ) {
            return SendResult::failure(
                errorMessage:
                    $exception
                        ->getMessage(),

                errorCode:
                    $exception
                        ->metaErrorCode
                    ?? 'meta_api_error',
            );
        } catch (Throwable $exception) {
            return SendResult::failure(
                errorMessage:
                    $exception
                        ->getMessage(),

                errorCode:
                    'whatsapp_send_error',
            );
        }
    }

    /**
     * Convert stored contact identity into the
     * WhatsApp recipient format.
     */
    private function normalizeRecipient(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $number =
            preg_replace(
                '/\D+/',
                '',
                $value
            ) ?? '';

        if ($number === '') {
            return null;
        }

        /*
         * Avoid obviously invalid numbers.
         *
         * We deliberately don't enforce one country's
         * phone format because this is multi-tenant
         * and international.
         */
        if (
            strlen($number) < 8
            || strlen($number) > 15
        ) {
            return null;
        }

        return $number;
    }

    /**
     * Extract Meta's outbound WhatsApp message ID.
     */
    private function extractMessageId(
        array $response
    ): ?string {
        $messages =
            $response['messages']
            ?? null;

        if (
            !is_array($messages)
            || !isset(
                $messages[0]
            )
            || !is_array(
                $messages[0]
            )
        ) {
            return null;
        }

        $id =
            trim(
                (string) (
                    $messages[0]['id']
                    ?? ''
                )
            );

        return $id !== ''
            ? $id
            : null;
    }
}