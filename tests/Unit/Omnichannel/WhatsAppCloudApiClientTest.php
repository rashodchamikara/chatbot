<?php

namespace Tests\Unit\Omnichannel;

use App\Exceptions\Omnichannel\WhatsAppCloudApiException;
use App\Services\Omnichannel\WhatsApp\WhatsAppCloudApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppCloudApiClientTest extends TestCase
{
    private WhatsAppCloudApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.graph_url' =>
                'https://graph.facebook.com',

            'services.meta.graph_version' =>
                'v26.0',

            'services.meta.timeout' =>
                15,
        ]);

        $this->client =
            new WhatsAppCloudApiClient();
    }

    public function test_phone_number_request_uses_correct_endpoint(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' =>
                    '987654321098765',

                'verified_name' =>
                    'Test Company',

                'display_phone_number' =>
                    '+94 77 123 4567',

                'quality_rating' =>
                    'GREEN',
            ], 200),
        ]);

        $response =
            $this->client
                ->phoneNumber(
                    phoneNumberId:
                        '987654321098765',

                    accessToken:
                        'TEST_ACCESS_TOKEN_1234567890',
                );

        $this->assertSame(
            '987654321098765',
            $response['id']
        );

        $this->assertSame(
            'Test Company',
            $response[
                'verified_name'
            ]
        );

        Http::assertSent(
            function ($request) {
                return
                    $request->method()
                        === 'GET'
                    &&
                    str_starts_with(
                        $request->url(),
                        'https://graph.facebook.com/v26.0/987654321098765'
                    )
                    &&
                    $request->hasHeader(
                        'Authorization',
                        'Bearer TEST_ACCESS_TOKEN_1234567890'
                    );
            }
        );
    }

    public function test_phone_number_request_contains_expected_fields(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' =>
                    '987654321098765',
            ], 200),
        ]);

        $this->client
            ->phoneNumber(
                phoneNumberId:
                    '987654321098765',

                accessToken:
                    'TEST_ACCESS_TOKEN_1234567890',
            );

        Http::assertSent(
            function ($request) {
                $url =
                    $request->url();

                return
                    str_contains(
                        $url,
                        'fields='
                    )
                    &&
                    str_contains(
                        urldecode($url),
                        'verified_name'
                    )
                    &&
                    str_contains(
                        urldecode($url),
                        'display_phone_number'
                    )
                    &&
                    str_contains(
                        urldecode($url),
                        'quality_rating'
                    )
                    &&
                str_contains(
                    urldecode($url),
                    'code_verification_status'
                );
            }
        );
    }

    public function test_invalid_phone_number_id_is_rejected(): void
    {
        $this->expectException(
            WhatsAppCloudApiException::class
        );

        $this->expectExceptionMessage(
            'A valid numeric WhatsApp Phone Number ID is required.'
        );

        $this->client
            ->phoneNumber(
                phoneNumberId:
                    'invalid-phone-id',

                accessToken:
                    'TEST_ACCESS_TOKEN_1234567890',
            );
    }

    public function test_empty_access_token_is_rejected(): void
    {
        $this->expectException(
            WhatsAppCloudApiException::class
        );

        $this->expectExceptionMessage(
            'Meta access token is required.'
        );

        $this->client->get(
            path:
                '987654321098765',

            accessToken:
                '',
        );
    }

    public function test_successful_get_returns_json_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => '123',
                'name' => 'Example',
            ], 200),
        ]);

        $response =
            $this->client->get(
                path:
                    '123',

                accessToken:
                    'TEST_ACCESS_TOKEN_1234567890',
            );

        $this->assertSame(
            '123',
            $response['id']
        );

        $this->assertSame(
            'Example',
            $response['name']
        );
    }

    public function test_successful_post_returns_json_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response =
            $this->client->post(
                path:
                    '123/test',

                accessToken:
                    'TEST_ACCESS_TOKEN_1234567890',

                body: [
                    'example' =>
                        'value',
                ],
            );

        $this->assertTrue(
            $response['success']
        );

        Http::assertSent(
            function ($request) {
                return
                    $request->method()
                        === 'POST'
                    &&
                    $request[
                        'example'
                    ] === 'value';
            }
        );
    }

    public function test_meta_error_response_throws_custom_exception(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' =>
                        'Invalid OAuth access token.',

                    'type' =>
                        'OAuthException',

                    'code' =>
                        190,

                    'fbtrace_id' =>
                        'TEST_TRACE_ID',
                ],
            ], 401),
        ]);

        try {
            $this->client->get(
                path:
                    '123',

                accessToken:
                    'INVALID_TOKEN_1234567890',
            );

            $this->fail(
                'Expected WhatsAppCloudApiException was not thrown.'
            );
        } catch (
            WhatsAppCloudApiException $exception
        ) {
            $this->assertSame(
                'Invalid OAuth access token.',
                $exception->getMessage()
            );

            $this->assertSame(
                401,
                $exception->httpStatus
            );

            $this->assertSame(
                '190',
                $exception->metaErrorCode
            );

            $this->assertSame(
                'TEST_TRACE_ID',
                $exception->metaTraceId
            );
        }
    }

    public function test_meta_error_subcode_is_preserved(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' =>
                        'Meta test error',

                    'code' =>
                        100,

                    'error_subcode' =>
                        33,

                    'fbtrace_id' =>
                        'TRACE_123',
                ],
            ], 400),
        ]);

        try {
            $this->client->get(
                path:
                    '123',

                accessToken:
                    'TEST_ACCESS_TOKEN_1234567890',
            );

            $this->fail(
                'Expected WhatsAppCloudApiException was not thrown.'
            );
        } catch (
            WhatsAppCloudApiException $exception
        ) {
            $this->assertSame(
                '100',
                $exception->metaErrorCode
            );

            $this->assertSame(
                '33',
                $exception->metaErrorSubcode
            );

            $this->assertSame(
                'TRACE_123',
                $exception->metaTraceId
            );
        }
    }

    public function test_http_error_without_meta_error_uses_fallback_message(): void
    {
        Http::fake([
            '*' => Http::response(
                [],
                500
            ),
        ]);

        try {
            $this->client->get(
                path:
                    '123',

                accessToken:
                    'TEST_ACCESS_TOKEN_1234567890',
            );

            $this->fail(
                'Expected WhatsAppCloudApiException was not thrown.'
            );
        } catch (
            WhatsAppCloudApiException $exception
        ) {
            $this->assertSame(
                500,
                $exception->httpStatus
            );

            $this->assertSame(
                'Meta Graph API request failed with HTTP status 500.',
                $exception->getMessage()
            );
        }
    }

    public function test_graph_api_version_is_configurable(): void
    {
        config([
            'services.meta.graph_version' =>
                'v99.0',
        ]);

        Http::fake([
            '*' => Http::response([
                'id' => '123',
            ], 200),
        ]);

        $this->client->get(
            path:
                '123',

            accessToken:
                'TEST_ACCESS_TOKEN_1234567890',
        );

        Http::assertSent(
            function ($request) {
                return str_starts_with(
                    $request->url(),
                    'https://graph.facebook.com/v99.0/123'
                );
            }
        );
    }
    public function test_waba_phone_numbers_request_uses_correct_endpoint(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    [
                        'id' =>
                            '987654321098765',
                    ],
                ],
            ], 200),
        ]);

        $response =
            $this->client
                ->phoneNumbersForBusinessAccount(
                    businessAccountId:
                        '123456789012345',

                    accessToken:
                        'TEST_ACCESS_TOKEN_1234567890',
                );

        $this->assertArrayHasKey(
            'data',
            $response
        );

        Http::assertSent(
            function ($request) {
                return
                    $request->method()
                        === 'GET'
                    &&
                    str_starts_with(
                        $request->url(),
                        'https://graph.facebook.com/v26.0/123456789012345/phone_numbers'
                    )
                    &&
                    $request->hasHeader(
                        'Authorization',
                        'Bearer TEST_ACCESS_TOKEN_1234567890'
                    );
            }
        );
    }
    public function test_invalid_waba_id_is_rejected(): void
    {
        $this->expectException(
            \App\Exceptions\Omnichannel\WhatsAppCloudApiException::class
        );

        $this->expectExceptionMessage(
            'A valid numeric WhatsApp Business Account ID is required.'
        );

        $this->client
            ->phoneNumbersForBusinessAccount(
                businessAccountId:
                    'invalid-waba',

                accessToken:
                    'TEST_ACCESS_TOKEN_1234567890',
            );
    }
}