<?php

namespace App\Services\Omnichannel\WhatsApp;

use App\Data\Omnichannel\WhatsAppConnectionHealthResult;
use App\Exceptions\Omnichannel\WhatsAppCloudApiException;
use App\Models\ChannelConnection;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;
use LogicException;

class WhatsAppConnectionHealthService
{
    public function __construct(
        private readonly WhatsAppCloudApiClient $client,
        private readonly WhatsAppConnectionConfigService $configService,
    ) {
    }

    /**
     * Verify a configured WhatsApp connection
     * against Meta.
     *
     * Successful verification:
     *
     * pending/error -> active
     *
     * Failed verification:
     *
     * pending/active -> error
     */
    public function check(
        ChannelConnection $connection,
    ): WhatsAppConnectionHealthResult {
        $checkedAt =
            now();

        /*
         * Keep the token only in memory.
         *
         * It is never included in providerData,
         * settings, result messages, or logs.
         */
        $accessToken =
            null;

        try {
            /*
             * Verify all locally required fields
             * before contacting Meta.
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

            $phoneNumberId =
                trim(
                    (string)
                    $connection
                        ->external_sender_id
                );

            $businessAccountId =
                trim(
                    (string)
                    $connection
                        ->external_account_id
                );

            /*
             * First provider check:
             *
             * Can this token read the configured
             * Meta WhatsApp Phone Number ID?
             */
            $phone =
                $this->client
                    ->phoneNumber(
                        phoneNumberId:
                            $phoneNumberId,

                        accessToken:
                            $accessToken,
                    );

            $returnedPhoneNumberId =
                trim(
                    (string) (
                        $phone['id']
                        ?? ''
                    )
                );

            if (
                $returnedPhoneNumberId
                === ''
            ) {
                throw new LogicException(
                    'Meta did not return a WhatsApp Phone Number ID.'
                );
            }

            if (
                $returnedPhoneNumberId
                !== $phoneNumberId
            ) {
                throw new LogicException(
                    'Meta returned a different WhatsApp Phone Number ID than the configured connection.'
                );
            }

            /*
             * Second provider check:
             *
             * Ensure the configured phone number
             * actually belongs to the configured
             * WhatsApp Business Account.
             */
            $wabaPhoneResponse =
                $this->client
                    ->phoneNumbersForBusinessAccount(
                        businessAccountId:
                            $businessAccountId,

                        accessToken:
                            $accessToken,
                    );

            $wabaPhone =
                $this->findPhoneNumber(
                    response:
                        $wabaPhoneResponse,

                    phoneNumberId:
                        $phoneNumberId,
                );

            if ($wabaPhone === null) {
                throw new LogicException(
                    'The configured WhatsApp Phone Number ID does not belong to the configured WhatsApp Business Account.'
                );
            }

            /*
             * Direct phone-number data is considered
             * the most specific source.
             */
            $providerData =
                array_merge(
                    $wabaPhone,
                    $phone,
                );

            $settings =
                $this->mergeProviderSettings(
                    existingSettings:
                        is_array(
                            $connection
                                ->settings
                        )
                            ? $connection
                                ->settings
                            : [],

                    providerData:
                        $providerData,
                );

            /*
             * Preserve connected_at after the
             * connection has already been verified
             * successfully once.
             */
            $connectedAt =
                $connection
                    ->connected_at
                ?? $checkedAt;

            $connection->forceFill([
                'status' =>
                    'active',

                'settings' =>
                    $settings,

                'connected_at' =>
                    $connectedAt,

                'last_health_check_at' =>
                    $checkedAt,

                'last_error' =>
                    null,
            ]);

            $connection->save();

            $connection =
                $connection->refresh();

            return WhatsAppConnectionHealthResult::healthy(
                connection:
                    $connection,

                providerData:
                    $providerData,
            );
        } catch (
            WhatsAppCloudApiException
            | LogicException
            | DecryptException
            $exception
        ) {
            $message =
                $this->safeErrorMessage(
                    message:
                        $exception
                            ->getMessage(),

                    accessToken:
                        $accessToken,
                );

            $connection->forceFill([
                'status' =>
                    'error',

                'last_health_check_at' =>
                    $checkedAt,

                'last_error' =>
                    $message,
            ]);

            $connection->save();

            $connection =
                $connection->refresh();

            return WhatsAppConnectionHealthResult::unhealthy(
                connection:
                    $connection,

                message:
                    $message,
            );
        }
    }

    /**
     * Find the configured Phone Number ID in the
     * WABA phone-number collection.
     */
    private function findPhoneNumber(
        array $response,
        string $phoneNumberId,
    ): ?array {
        $data =
            $response['data']
            ?? null;

        if (!is_array($data)) {
            throw new LogicException(
                'Meta returned an invalid WhatsApp Business Account phone-number response.'
            );
        }

        foreach ($data as $phone) {
            if (!is_array($phone)) {
                continue;
            }

            $id =
                trim(
                    (string) (
                        $phone['id']
                        ?? ''
                    )
                );

            if ($id === $phoneNumberId) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * Copy safe provider metadata into the
     * connection settings.
     *
     * Access tokens must NEVER be placed here.
     */
    private function mergeProviderSettings(
        array $existingSettings,
        array $providerData,
    ): array {
        $settings =
            $existingSettings;

        $this->copySetting(
            settings:
                $settings,

            providerData:
                $providerData,

            source:
                'verified_name',

            destination:
                'verified_name',
        );

        $this->copySetting(
            settings:
                $settings,

            providerData:
                $providerData,

            source:
                'display_phone_number',

            destination:
                'display_phone_number',
        );

        $this->copySetting(
            settings:
                $settings,

            providerData:
                $providerData,

            source:
                'quality_rating',

            destination:
                'quality_rating',
        );

        $this->copySetting(
            settings:
                $settings,

            providerData:
                $providerData,

            source:
                'code_verification_status',

            destination:
                'code_verification_status',
        );

        return $settings;
    }

    /**
     * Copy a provider value only when it actually
     * exists.
     *
     * Existing local settings are therefore not
     * destroyed if Meta omits an optional field.
     */
    private function copySetting(
        array &$settings,
        array $providerData,
        string $source,
        string $destination,
    ): void {
        if (
            !array_key_exists(
                $source,
                $providerData
            )
        ) {
            return;
        }

        $value =
            $providerData[
                $source
            ];

        if (
            $value === null
            || (
                is_string($value)
                && trim($value) === ''
            )
        ) {
            return;
        }

        $settings[
            $destination
        ] = $value;
    }

    /**
     * Prevent credentials from accidentally being
     * persisted inside last_error.
     */
    private function safeErrorMessage(
        string $message,
        ?string $accessToken = null,
    ): string {
        $message =
            trim(
                $message
            );

        if ($message === '') {
            $message =
                'WhatsApp connection verification failed.';
        }

        if (
            $accessToken !== null
            && $accessToken !== ''
        ) {
            $message =
                str_replace(
                    $accessToken,
                    '[REDACTED]',
                    $message
                );
        }

        /*
         * last_error is a text column, but there is
         * no reason to persist huge provider errors.
         */
        return Str::limit(
            $message,
            2000,
            ''
        );
    }
}