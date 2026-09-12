<?php

namespace Tests\Unit\Omnichannel;

use App\Models\ChannelConnection;
use App\Services\Omnichannel\WhatsApp\WhatsAppCloudApiClient;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionConfigService;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionHealthService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppConnectionHealthServiceTest extends TestCase
{
    private WhatsAppConnectionHealthService $service;

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

        $this->service =
            new WhatsAppConnectionHealthService(
                client:
                    new WhatsAppCloudApiClient(),

                configService:
                    new WhatsAppConnectionConfigService(),
            );
    }

    public function test_successful_health_check_marks_connection_active(): void
    {
        Http::fake([
            'https://graph.facebook.com/v26.0/987654321098765*'
                => Http::response([
                    'id' =>
                        '987654321098765',

                    'verified_name' =>
                        'Test Company',

                    'display_phone_number' =>
                        '+94 77 123 4567',

                    'quality_rating' =>
                        'GREEN',

                    'code_verification_status' =>
                        'VERIFIED',
                ], 200),

            'https://graph.facebook.com/v26.0/123456789012345/phone_numbers*'
                => Http::response([
                    'data' => [
                        [
                            'id' =>
                                '987654321098765',

                            'verified_name' =>
                                'Test Company',

                            'display_phone_number' =>
                                '+94 77 123 4567',

                            'quality_rating' =>
                                'GREEN',
                        ],
                    ],
                ], 200),
        ]);

        $connection =
            $this->makeConnection();

        $result =
            $this->service
                ->check(
                    $connection
                );

        $this->assertTrue(
            $result->healthy
        );

        $this->assertSame(
            'active',
            $result->connection->status
        );

        $this->assertNotNull(
            $result
                ->connection
                ->connected_at
        );

        $this->assertNotNull(
            $result
                ->connection
                ->last_health_check_at
        );

        $this->assertNull(
            $result
                ->connection
                ->last_error
        );

        $this->assertTrue(
            $connection->wasSaved
        );
    }

    public function test_successful_health_check_updates_provider_metadata(): void
    {
        Http::fake([
            'https://graph.facebook.com/v26.0/987654321098765*'
                => Http::response([
                    'id' =>
                        '987654321098765',

                    'verified_name' =>
                        'Verified Company',

                    'display_phone_number' =>
                        '+94 77 555 5555',

                    'quality_rating' =>
                        'GREEN',

                    'code_verification_status' =>
                        'VERIFIED',
                ], 200),

            'https://graph.facebook.com/v26.0/123456789012345/phone_numbers*'
                => Http::response([
                    'data' => [
                        [
                            'id' =>
                                '987654321098765',
                        ],
                    ],
                ], 200),
        ]);

        $connection =
            $this->makeConnection();

        $result =
            $this->service
                ->check(
                    $connection
                );

        $settings =
            $result
                ->connection
                ->settings;

        $this->assertSame(
            'Verified Company',
            $settings[
                'verified_name'
            ]
        );

        $this->assertSame(
            '+94 77 555 5555',
            $settings[
                'display_phone_number'
            ]
        );

        $this->assertSame(
            'GREEN',
            $settings[
                'quality_rating'
            ]
        );

        $this->assertSame(
            'VERIFIED',
            $settings[
                'code_verification_status'
            ]
        );

        /*
         * Existing settings must survive.
         */
        $this->assertSame(
            'keep-me',
            $settings[
                'existing_setting'
            ]
        );
    }

    public function test_phone_number_not_in_waba_marks_connection_error(): void
    {
        Http::fake([
            'https://graph.facebook.com/v26.0/987654321098765*'
                => Http::response([
                    'id' =>
                        '987654321098765',

                    'verified_name' =>
                        'Test Company',
                ], 200),

            'https://graph.facebook.com/v26.0/123456789012345/phone_numbers*'
                => Http::response([
                    'data' => [
                        [
                            'id' =>
                                '111111111111111',
                        ],
                    ],
                ], 200),
        ]);

        $connection =
            $this->makeConnection();

        $result =
            $this->service
                ->check(
                    $connection
                );

        $this->assertFalse(
            $result->healthy
        );

        $this->assertSame(
            'error',
            $result
                ->connection
                ->status
        );

        $this->assertSame(
            'The configured WhatsApp Phone Number ID does not belong to the configured WhatsApp Business Account.',
            $result
                ->connection
                ->last_error
        );

        $this->assertNotNull(
            $result
                ->connection
                ->last_health_check_at
        );
    }

    public function test_meta_authentication_error_marks_connection_error(): void
    {
        Http::fake([
            '*' =>
                Http::response([
                    'error' => [
                        'message' =>
                            'Invalid OAuth access token.',

                        'type' =>
                            'OAuthException',

                        'code' =>
                            190,

                        'fbtrace_id' =>
                            'TEST_TRACE',
                    ],
                ], 401),
        ]);

        $connection =
            $this->makeConnection();

        $result =
            $this->service
                ->check(
                    $connection
                );

        $this->assertFalse(
            $result->healthy
        );

        $this->assertSame(
            'error',
            $result
                ->connection
                ->status
        );

        $this->assertSame(
            'Invalid OAuth access token.',
            $result
                ->connection
                ->last_error
        );
    }

    public function test_meta_returning_different_phone_id_marks_connection_error(): void
    {
        Http::fake([
            '*' =>
                Http::response([
                    'id' =>
                        '111111111111111',
                ], 200),
        ]);

        $connection =
            $this->makeConnection();

        $result =
            $this->service
                ->check(
                    $connection
                );

        $this->assertFalse(
            $result->healthy
        );

        $this->assertSame(
            'error',
            $result
                ->connection
                ->status
        );

        $this->assertSame(
            'Meta returned a different WhatsApp Phone Number ID than the configured connection.',
            $result
                ->connection
                ->last_error
        );
    }

    public function test_existing_connected_at_is_preserved(): void
    {
        $originalConnectedAt =
            Carbon::parse(
                '2026-09-01 10:00:00'
            );

        Http::fake([
            'https://graph.facebook.com/v26.0/987654321098765*'
                => Http::response([
                    'id' =>
                        '987654321098765',
                ], 200),

            'https://graph.facebook.com/v26.0/123456789012345/phone_numbers*'
                => Http::response([
                    'data' => [
                        [
                            'id' =>
                                '987654321098765',
                        ],
                    ],
                ], 200),
        ]);

        $connection =
            $this->makeConnection();

        $connection->connected_at =
            $originalConnectedAt;

        $result =
            $this->service
                ->check(
                    $connection
                );

        $this->assertTrue(
            $result->healthy
        );

        $this->assertTrue(
            $result
                ->connection
                ->connected_at
                ->equalTo(
                    $originalConnectedAt
                )
        );
    }

    public function test_previous_error_is_cleared_after_recovery(): void
    {
        Http::fake([
            'https://graph.facebook.com/v26.0/987654321098765*'
                => Http::response([
                    'id' =>
                        '987654321098765',
                ], 200),

            'https://graph.facebook.com/v26.0/123456789012345/phone_numbers*'
                => Http::response([
                    'data' => [
                        [
                            'id' =>
                                '987654321098765',
                        ],
                    ],
                ], 200),
        ]);

        $connection =
            $this->makeConnection();

        $connection->status =
            'error';

        $connection->last_error =
            'Previous provider failure.';

        $result =
            $this->service
                ->check(
                    $connection
                );

        $this->assertTrue(
            $result->healthy
        );

        $this->assertSame(
            'active',
            $result
                ->connection
                ->status
        );

        $this->assertNull(
            $result
                ->connection
                ->last_error
        );
    }

    public function test_invalid_local_configuration_marks_connection_error_without_http_request(): void
    {
        Http::fake();

        $connection =
            $this->makeConnection();

        $connection
            ->external_sender_id =
            null;

        $result =
            $this->service
                ->check(
                    $connection
                );

        $this->assertFalse(
            $result->healthy
        );

        $this->assertSame(
            'error',
            $result
                ->connection
                ->status
        );

        $this->assertStringContainsString(
            'WhatsApp Phone Number ID is missing.',
            $result
                ->connection
                ->last_error
        );

        Http::assertNothingSent();
    }

    private function makeConnection(): HealthTestChannelConnection
    {
        $connection =
            new HealthTestChannelConnection();

        $connection->forceFill([
            'type' =>
                'whatsapp',

            'provider' =>
                'meta',

            'status' =>
                'pending',

            'external_account_id' =>
                '123456789012345',

            'external_sender_id' =>
                '987654321098765',

            'credentials' => [
                'access_token' =>
                    'TEST_ACCESS_TOKEN_1234567890',
            ],

            'settings' => [
                'existing_setting' =>
                    'keep-me',
            ],

            'connected_at' =>
                null,

            'last_health_check_at' =>
                null,

            'last_error' =>
                null,
        ]);

        return $connection;
    }
}

/**
 * In-memory model used only by this unit test.
 *
 * Do NOT call this TestChannelConnection because
 * Sprint 2.8.2.4 already defined a test double with
 * that name in the same namespace.
 */
class HealthTestChannelConnection extends ChannelConnection
{
    public bool $wasSaved = false;

    public function save(array $options = [])
    {
        $this->wasSaved =
            true;

        return true;
    }

    public function refresh()
    {
        return $this;
    }
}