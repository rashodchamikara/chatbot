<?php

namespace Tests\Unit\Omnichannel;

use App\Data\Omnichannel\WhatsAppConnectionData;
use App\Models\ChannelConnection;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionConfigService;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class WhatsAppConnectionConfigServiceTest extends TestCase
{
    private WhatsAppConnectionConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service =
            new WhatsAppConnectionConfigService();
    }

    public function test_valid_whatsapp_data_passes_validation(): void
    {
        $data =
            new WhatsAppConnectionData(
                accessToken:
                    'EAAG_TEST_ACCESS_TOKEN_1234567890',

                businessAccountId:
                    '123456789012345',

                phoneNumberId:
                    '987654321098765',

                displayPhoneNumber:
                    '+94 77 123 4567',

                verifiedName:
                    'Test Company',
            );

        $this->service->validateData(
            $data
        );

        $this->assertTrue(true);
    }

    public function test_invalid_business_account_id_fails_validation(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $data =
            new WhatsAppConnectionData(
                accessToken:
                    'EAAG_TEST_ACCESS_TOKEN_1234567890',

                businessAccountId:
                    'invalid-waba-id',

                phoneNumberId:
                    '987654321098765',
            );

        $this->service->validateData(
            $data
        );
    }

    public function test_invalid_phone_number_id_fails_validation(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $data =
            new WhatsAppConnectionData(
                accessToken:
                    'EAAG_TEST_ACCESS_TOKEN_1234567890',

                businessAccountId:
                    '123456789012345',

                phoneNumberId:
                    'invalid-phone-id',
            );

        $this->service->validateData(
            $data
        );
    }

    public function test_short_access_token_fails_validation(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $data =
            new WhatsAppConnectionData(
                accessToken:
                    'short-token',

                businessAccountId:
                    '123456789012345',

                phoneNumberId:
                    '987654321098765',
            );

        $this->service->validateData(
            $data
        );
    }

    public function test_complete_whatsapp_connection_is_configuration_ready(): void
    {
        $connection =
            $this->makeReadyConnection();

        $this->assertTrue(
            $this->service
                ->isConfigurationReady(
                    $connection
                )
        );

        $this->assertSame(
            [],
            $this->service
                ->configurationErrors(
                    $connection
                )
        );
    }

    public function test_missing_access_token_makes_connection_not_ready(): void
    {
        $connection =
            new ChannelConnection();

        $connection->forceFill([
            'type' =>
                'whatsapp',

            'provider' =>
                'meta',

            'external_account_id' =>
                '123456789012345',

            'external_sender_id' =>
                '987654321098765',

            'credentials' =>
                [],

            'settings' =>
                [],
        ]);

        $errors =
            $this->service
                ->configurationErrors(
                    $connection
                );

        $this->assertFalse(
            $this->service
                ->isConfigurationReady(
                    $connection
                )
        );

        $this->assertContains(
            'WhatsApp access token is missing.',
            $errors
        );
    }

    public function test_missing_business_account_id_makes_connection_not_ready(): void
    {
        $connection =
            $this->makeReadyConnection();

        $connection->external_account_id =
            null;

        $errors =
            $this->service
                ->configurationErrors(
                    $connection
                );

        $this->assertContains(
            'WhatsApp Business Account ID is missing.',
            $errors
        );
    }

    public function test_missing_phone_number_id_makes_connection_not_ready(): void
    {
        $connection =
            $this->makeReadyConnection();

        $connection->external_sender_id =
            null;

        $errors =
            $this->service
                ->configurationErrors(
                    $connection
                );

        $this->assertContains(
            'WhatsApp Phone Number ID is missing.',
            $errors
        );
    }

    public function test_non_numeric_business_account_id_is_rejected(): void
    {
        $connection =
            $this->makeReadyConnection();

        $connection->external_account_id =
            'abc123';

        $errors =
            $this->service
                ->configurationErrors(
                    $connection
                );

        $this->assertContains(
            'WhatsApp Business Account ID must be numeric.',
            $errors
        );
    }

    public function test_non_numeric_phone_number_id_is_rejected(): void
    {
        $connection =
            $this->makeReadyConnection();

        $connection->external_sender_id =
            'phone-123';

        $errors =
            $this->service
                ->configurationErrors(
                    $connection
                );

        $this->assertContains(
            'WhatsApp Phone Number ID must be numeric.',
            $errors
        );
    }

    public function test_non_whatsapp_connection_is_not_ready(): void
    {
        $connection =
            $this->makeReadyConnection();

        $connection->type =
            'website';

        $errors =
            $this->service
                ->configurationErrors(
                    $connection
                );

        $this->assertContains(
            'Channel connection type must be whatsapp.',
            $errors
        );

        $this->assertFalse(
            $this->service
                ->isConfigurationReady(
                    $connection
                )
        );
    }

    public function test_wrong_provider_is_not_ready(): void
    {
        $connection =
            $this->makeReadyConnection();

        $connection->provider =
            'twilio';

        $errors =
            $this->service
                ->configurationErrors(
                    $connection
                );

        $this->assertContains(
            'WhatsApp provider must be meta.',
            $errors
        );
    }

    public function test_assert_configuration_ready_throws_for_invalid_connection(): void
    {
        $this->expectException(
            LogicException::class
        );

        $connection =
            new ChannelConnection();

        $connection->forceFill([
            'type' =>
                'whatsapp',

            'provider' =>
                'meta',

            'external_account_id' =>
                null,

            'external_sender_id' =>
                null,

            'credentials' =>
                null,

            'settings' =>
                [],
        ]);

        $this->service
            ->assertConfigurationReady(
                $connection
            );
    }

    public function test_access_token_returns_decrypted_token(): void
    {
        $connection =
            $this->makeReadyConnection();

        $token =
            $this->service
                ->accessToken(
                    $connection
                );

        $this->assertSame(
            'EAAG_TEST_ACCESS_TOKEN_1234567890',
            $token
        );
    }

    public function test_credentials_are_encrypted_in_raw_model_attributes(): void
    {
        $connection =
            new ChannelConnection();

        $plainToken =
            'EAAG_SUPER_SECRET_TOKEN_1234567890';

        $connection->credentials = [
            'access_token' =>
                $plainToken,
        ];

        /*
         * Normal Eloquent access should decrypt
         * the credentials automatically.
         */
        $this->assertSame(
            $plainToken,
            $connection->credentials[
                'access_token'
            ]
        );

        /*
         * Raw model data should contain ciphertext,
         * not the plaintext access token.
         */
        $raw =
            $connection->getAttributes()[
                'credentials'
            ] ?? null;

        $this->assertIsString(
            $raw
        );

        $this->assertStringNotContainsString(
            $plainToken,
            $raw
        );
    }

    public function test_configure_populates_whatsapp_connection(): void
    {
        $connection =
            new TestChannelConnection();

        $connection->forceFill([
            'type' =>
                'whatsapp',

            'provider' =>
                'meta',

            'settings' => [
                'existing_setting' =>
                    'keep-me',
            ],
        ]);

        $data =
            new WhatsAppConnectionData(
                accessToken:
                    'EAAG_TEST_ACCESS_TOKEN_1234567890',

                businessAccountId:
                    '123456789012345',

                phoneNumberId:
                    '987654321098765',

                displayPhoneNumber:
                    '+94 77 123 4567',

                verifiedName:
                    'Test Company',

                settings: [
                    'quality_monitoring' =>
                        true,
                ],
            );

        $result =
            $this->service->configure(
                $connection,
                $data
            );

        $this->assertSame(
            'meta',
            $result->provider
        );

        $this->assertSame(
            '123456789012345',
            $result->external_account_id
        );

        $this->assertSame(
            '987654321098765',
            $result->external_sender_id
        );

        $this->assertSame(
            'pending',
            $result->status
        );

        $this->assertNull(
            $result->connected_at
        );

        $this->assertNull(
            $result->last_health_check_at
        );

        $this->assertNull(
            $result->last_error
        );

        $this->assertSame(
            'EAAG_TEST_ACCESS_TOKEN_1234567890',
            $result->credentials[
                'access_token'
            ]
        );

        $this->assertSame(
            'keep-me',
            $result->settings[
                'existing_setting'
            ]
        );

        $this->assertTrue(
            $result->settings[
                'quality_monitoring'
            ]
        );

        $this->assertSame(
            '+94 77 123 4567',
            $result->settings[
                'display_phone_number'
            ]
        );

        $this->assertSame(
            'Test Company',
            $result->settings[
                'verified_name'
            ]
        );

        $this->assertTrue(
            $result->wasSaved
        );
    }

    public function test_configure_rejects_non_whatsapp_connection(): void
    {
        $this->expectException(
            LogicException::class
        );

        $connection =
            new TestChannelConnection();

        $connection->forceFill([
            'type' =>
                'website',

            'provider' =>
                'native',

            'settings' =>
                [],
        ]);

        $data =
            new WhatsAppConnectionData(
                accessToken:
                    'EAAG_TEST_ACCESS_TOKEN_1234567890',

                businessAccountId:
                    '123456789012345',

                phoneNumberId:
                    '987654321098765',
            );

        $this->service->configure(
            $connection,
            $data
        );
    }

    private function makeReadyConnection(): ChannelConnection
    {
        $connection =
            new ChannelConnection();

        $connection->forceFill([
            'type' =>
                'whatsapp',

            'provider' =>
                'meta',

            'external_account_id' =>
                '123456789012345',

            'external_sender_id' =>
                '987654321098765',

            'credentials' => [
                'access_token' =>
                    'EAAG_TEST_ACCESS_TOKEN_1234567890',
            ],

            'settings' =>
                [],
        ]);

        return $connection;
    }
}

/**
 * Small in-memory ChannelConnection test double.
 *
 * configure() normally calls save() and refresh().
 * We override those methods so this remains a true
 * unit test and does not require tenants, ai_agents,
 * webhook keys, or database foreign keys.
 */
class TestChannelConnection extends ChannelConnection
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