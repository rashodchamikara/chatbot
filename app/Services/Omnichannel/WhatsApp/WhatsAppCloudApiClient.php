<?php

namespace App\Services\Omnichannel\WhatsApp;

use App\Exceptions\Omnichannel\WhatsAppCloudApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppCloudApiClient
{
    /**
     * Perform a GET request against the
     * configured Meta Graph API.
     *
     * @throws WhatsAppCloudApiException
     */
    public function get(
        string $path,
        string $accessToken,
        array $query = [],
    ): array {
        return $this->request(
            method: 'GET',
            path: $path,
            accessToken: $accessToken,
            data: $query,
        );
    }

    /**
     * Perform a POST request against the
     * configured Meta Graph API.
     *
     * We won't use this for sending WhatsApp
     * messages until a later Sprint.
     *
     * @throws WhatsAppCloudApiException
     */
    public function post(
        string $path,
        string $accessToken,
        array $body = [],
    ): array {
        return $this->request(
            method: 'POST',
            path: $path,
            accessToken: $accessToken,
            data: $body,
        );
    }

    /**
     * Retrieve information about a Meta
     * WhatsApp Phone Number ID.
     *
     * This will be used in Sprint 2.8.3.2
     * to verify a configured connection.
     *
     * @throws WhatsAppCloudApiException
     */
    public function phoneNumber(
        string $phoneNumberId,
        string $accessToken,
    ): array {
        $phoneNumberId =
            trim($phoneNumberId);

        if (
            $phoneNumberId === ''
            || !ctype_digit($phoneNumberId)
        ) {
            throw new WhatsAppCloudApiException(
                message:
                    'A valid numeric WhatsApp Phone Number ID is required.'
            );
        }

        return $this->get(
            path:
                $phoneNumberId,

            accessToken:
                $accessToken,

            query: [
                'fields' =>
                    implode(
                        ',',
                        [
                            'id',
                            'verified_name',
                            'display_phone_number',
                            'quality_rating',
                            'code_verification_status',
                        ]
                    ),
            ],
        );
    }

        /**
     * Retrieve the phone numbers belonging to a
     * WhatsApp Business Account.
     *
     * This allows us to verify that the configured
     * Phone Number ID actually belongs to the
     * configured WABA.
     *
     * @throws WhatsAppCloudApiException
     */
public function phoneNumbersForBusinessAccount(
        string $businessAccountId,
        string $accessToken,
    ): array {
        $businessAccountId =
            trim(
                $businessAccountId
            );

        if (
            $businessAccountId === ''
            || !ctype_digit(
                $businessAccountId
            )
        ) {
            throw new WhatsAppCloudApiException(
                message:
                    'A valid numeric WhatsApp Business Account ID is required.'
            );
        }

        return $this->get(
            path:
                $businessAccountId
                . '/phone_numbers',

            accessToken:
                $accessToken,

            query: [
                'fields' =>
                    implode(
                        ',',
                        [
                            'id',
                            'verified_name',
                            'display_phone_number',
                            'quality_rating',
                        ]
                    ),

                /*
                * A WABA normally contains very few
                * numbers. 100 gives us enough room
                * without making an unbounded request.
                */
                'limit' =>
                    100,
            ],
        );
    }


    /**
     * Execute an HTTP request.
     *
     * @throws WhatsAppCloudApiException
     */
    private function request(
        string $method,
        string $path,
        string $accessToken,
        array $data = [],
    ): array {
        $accessToken =
            trim($accessToken);

        if ($accessToken === '') {
            throw new WhatsAppCloudApiException(
                message:
                    'Meta access token is required.'
            );
        }

        $url =
            $this->buildUrl(
                $path
            );

        try {
            $request =
                Http::acceptJson()
                    ->asJson()
                    ->withToken(
                        $accessToken
                    )
                    ->timeout(
                        $this->timeout()
                    )
                    ->connectTimeout(
                        min(
                            10,
                            $this->timeout()
                        )
                    );

            $response =
                match (
                    strtoupper($method)
                ) {
                    'GET' =>
                        $request->get(
                            $url,
                            $data
                        ),

                    'POST' =>
                        $request->post(
                            $url,
                            $data
                        ),

                    default =>
                        throw new WhatsAppCloudApiException(
                            message:
                                sprintf(
                                    'Unsupported Meta HTTP method: %s',
                                    $method
                                )
                        ),
                };
        } catch (ConnectionException $exception) {
            throw new WhatsAppCloudApiException(
                message:
                    'Unable to connect to the Meta Graph API.',

                previous:
                    $exception,
            );
        } catch (WhatsAppCloudApiException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new WhatsAppCloudApiException(
                message:
                    'Unexpected error while communicating with the Meta Graph API.',

                previous:
                    $exception,
            );
        }

        if ($response->failed()) {
            throw $this->exceptionFromResponse(
                $response
            );
        }

        $payload =
            $response->json();

        if (!is_array($payload)) {
            throw new WhatsAppCloudApiException(
                message:
                    'Meta Graph API returned an invalid JSON response.',

                httpStatus:
                    $response->status(),
            );
        }

        /*
         * Occasionally an API can return HTTP 200
         * while still containing an error object.
         * Treat that consistently as a failure.
         */
        if (
            isset($payload['error'])
            && is_array(
                $payload['error']
            )
        ) {
            throw $this->exceptionFromResponse(
                $response
            );
        }

        return $payload;
    }

    /**
     * Construct the complete versioned Graph API URL.
     */
    private function buildUrl(
        string $path
    ): string {
        $baseUrl =
            rtrim(
                (string) config(
                    'services.meta.graph_url',
                    'https://graph.facebook.com'
                ),
                '/'
            );

        $version =
            trim(
                (string) config(
                    'services.meta.graph_version',
                    'v26.0'
                ),
                '/'
            );

        $path =
            ltrim(
                trim($path),
                '/'
            );

        if ($baseUrl === '') {
            throw new WhatsAppCloudApiException(
                message:
                    'Meta Graph API URL is not configured.'
            );
        }

        if ($version === '') {
            throw new WhatsAppCloudApiException(
                message:
                    'Meta Graph API version is not configured.'
            );
        }

        if ($path === '') {
            throw new WhatsAppCloudApiException(
                message:
                    'Meta Graph API request path is required.'
            );
        }

        return sprintf(
            '%s/%s/%s',
            $baseUrl,
            $version,
            $path
        );
    }

    /**
     * Convert Meta's HTTP/error structure into
     * our application-specific exception.
     */
    private function exceptionFromResponse(
        Response $response
    ): WhatsAppCloudApiException {
        $payload =
            $response->json();

        if (!is_array($payload)) {
            $payload = [];
        }

        $error =
            isset($payload['error'])
            && is_array(
                $payload['error']
            )
                ? $payload['error']
                : [];

        $message =
            trim(
                (string) (
                    $error['message']
                    ?? ''
                )
            );

        if ($message === '') {
            $message =
                sprintf(
                    'Meta Graph API request failed with HTTP status %d.',
                    $response->status()
                );
        }

        $code =
            isset($error['code'])
                ? (string) $error['code']
                : null;

        $subcode =
            isset(
                $error['error_subcode']
            )
                ? (string)
                    $error[
                        'error_subcode'
                    ]
                : null;

        $traceId =
            isset(
                $error['fbtrace_id']
            )
                ? (string)
                    $error[
                        'fbtrace_id'
                    ]
                : null;

        return new WhatsAppCloudApiException(
            message:
                $message,

            httpStatus:
                $response->status(),

            metaErrorCode:
                $code,

            metaErrorSubcode:
                $subcode,

            metaTraceId:
                $traceId,

            responseData:
                $payload,
        );
    }

    private function timeout(): int
    {
        $timeout =
            (int) config(
                'services.meta.timeout',
                15
            );

        /*
         * Protect us from invalid configuration
         * such as timeout=0.
         */
        return max(
            1,
            $timeout
        );
    }
    /**
     * Send a WhatsApp text message.
     *
     * @throws WhatsAppCloudApiException
    */
    public function sendTextMessage(
        string $phoneNumberId,
        string $accessToken,
        string $recipient,
        string $body,
        bool $previewUrl = false,
        ?string $replyToMessageId = null,
    ): array {
        $phoneNumberId =
            trim(
                $phoneNumberId
            );

        if (
            $phoneNumberId === ''
            || !ctype_digit(
                $phoneNumberId
            )
        ) {
            throw new WhatsAppCloudApiException(
                message:
                    'A valid numeric WhatsApp Phone Number ID is required.'
            );
        }

        /*
        * WhatsApp recipient identifiers are phone
        * numbers / wa_id values with country code.
        *
        * Remove +, spaces, brackets and dashes.
        *
        * Example:
        * +94 77 123 4567
        * becomes:
        * 94771234567
        */
        $recipient =
            preg_replace(
                '/\D+/',
                '',
                $recipient
            ) ?? '';

        if ($recipient === '') {
            throw new WhatsAppCloudApiException(
                message:
                    'A valid WhatsApp recipient number is required.'
            );
        }

        $body =
            trim(
                $body
            );

        if ($body === '') {
            throw new WhatsAppCloudApiException(
                message:
                    'WhatsApp text message body cannot be empty.'
            );
        }

        /*
        * Meta currently limits text message bodies
        * to 4096 characters.
        */
        if (
            mb_strlen($body)
            > 4096
        ) {
            throw new WhatsAppCloudApiException(
                message:
                    'WhatsApp text message body cannot exceed 4096 characters.'
            );
        }

        $payload = [
            'messaging_product' =>
                'whatsapp',

            'recipient_type' =>
                'individual',

            'to' =>
                $recipient,

            'type' =>
                'text',

            'text' => [
                'preview_url' =>
                    $previewUrl,

                'body' =>
                    $body,
            ],
        ];

        /*
        * Preserve native WhatsApp reply context when
        * replying to a specific incoming message.
        */
        if (
            $replyToMessageId !== null
            && trim(
                $replyToMessageId
            ) !== ''
        ) {
            $payload['context'] = [
                'message_id' =>
                    trim(
                        $replyToMessageId
                    ),
            ];
        }

        return $this->post(
            path:
                $phoneNumberId
                . '/messages',

            accessToken:
                $accessToken,

            body:
                $payload,
        );
    }
}