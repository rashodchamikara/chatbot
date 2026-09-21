<?php

namespace App\Services\Omnichannel\WhatsApp;

use App\Data\Omnichannel\WhatsAppConnectionData;
use App\Models\ChannelConnection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

class WhatsAppConnectionConfigService
{
    public const CHANNEL_TYPE = 'whatsapp';

    public const PROVIDER = 'meta';

    /**
     * Configure a ChannelConnection for
     * Meta WhatsApp Cloud API.
     *
     * This does NOT verify the connection
     * against Meta yet.
     */
    public function configure(
        ChannelConnection $connection,
        WhatsAppConnectionData $data,
    ): ChannelConnection {
        $this->assertWhatsAppConnection(
            $connection
        );

        $this->validateData(
            $data
        );

        $existingSettings =
            is_array($connection->settings)
                ? $connection->settings
                : [];

        $settings = array_merge(
            $existingSettings,
            $data->connectionSettings(),
        );

        /*
         * Preserve provider secrets that are not being replaced by this
         * configuration call (for example the Embedded Signup 2FA PIN).
         * The new access token / explicitly supplied values always win.
         */
        $existingCredentials = is_array($connection->credentials)
            ? $connection->credentials
            : [];

        $credentials = array_merge(
            $existingCredentials,
            $data->credentials(),
        );

        $connection->forceFill([
            'provider' =>
                self::PROVIDER,

            /*
             * Meta WhatsApp Business Account ID.
             */
            'external_account_id' =>
                trim(
                    $data->businessAccountId
                ),

            /*
             * Meta WhatsApp Phone Number ID.
             */
            'external_sender_id' =>
                trim(
                    $data->phoneNumberId
                ),

            /*
             * Automatically encrypted by
             * ChannelConnection's
             * encrypted:array cast.
             */
            'credentials' =>
                $credentials,

            'settings' =>
                $settings,

            /*
             * Credentials existing does not mean
             * Meta has accepted them.
             *
             * A provider health check will mark
             * this active later.
             */
            'status' =>
                'pending',

            'connected_at' =>
                null,

            'last_health_check_at' =>
                null,

            'last_error' =>
                null,
        ]);

        /*
         * Confirm the model now contains everything
         * required before persisting it.
         */
        $this->assertConfigurationReady(
            $connection
        );

        $connection->save();

        return $connection->refresh();
    }

    /**
     * Validate input received from an admin,
     * controller, command, Embedded Signup flow,
     * etc.
     *
     * @throws ValidationException
     */
    public function validateData(
        WhatsAppConnectionData $data,
    ): void {
        Validator::make(
            [
                'access_token' =>
                    trim(
                        $data->accessToken
                    ),

                'business_account_id' =>
                    trim(
                        $data->businessAccountId
                    ),

                'phone_number_id' =>
                    trim(
                        $data->phoneNumberId
                    ),

                'display_phone_number' =>
                    $data->displayPhoneNumber,

                'verified_name' =>
                    $data->verifiedName,
            ],
            [
                'access_token' => [
                    'required',
                    'string',
                    'min:20',
                ],

                /*
                 * Meta object IDs are numeric strings.
                 */
                'business_account_id' => [
                    'required',
                    'string',
                    'regex:/^\d+$/',
                    'max:191',
                ],

                'phone_number_id' => [
                    'required',
                    'string',
                    'regex:/^\d+$/',
                    'max:191',
                ],

                'display_phone_number' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'verified_name' => [
                    'nullable',
                    'string',
                    'max:191',
                ],
            ],
        )->validate();
    }

    /**
     * Determine whether this connection contains
     * enough configuration to attempt a Meta API
     * request.
     */
    public function isConfigurationReady(
        ChannelConnection $connection,
    ): bool {
        return $this->configurationErrors(
            $connection
        ) === [];
    }

    /**
     * Return human-readable configuration
     * problems.
     *
     * @return array<int, string>
     */
    public function configurationErrors(
        ChannelConnection $connection,
    ): array {
        $errors = [];

        /*
        * Channel type.
        */
        $type = strtolower(
            trim(
                (string) $connection->type
            )
        );

        if ($type !== self::CHANNEL_TYPE) {
            $errors[] =
                'Channel connection type must be whatsapp.';
        }

        /*
        * Provider.
        */
        $provider = strtolower(
            trim(
                (string) $connection->provider
            )
        );

        if ($provider !== self::PROVIDER) {
            $errors[] =
                'WhatsApp provider must be meta.';
        }

        /*
        * WhatsApp Business Account ID / WABA ID.
        */
        $businessAccountId = trim(
            (string) $connection->external_account_id
        );

        if ($businessAccountId === '') {
            $errors[] =
                'WhatsApp Business Account ID is missing.';
        } elseif (!ctype_digit($businessAccountId)) {
            $errors[] =
                'WhatsApp Business Account ID must be numeric.';
        }

        /*
        * Meta Phone Number ID.
        */
        $phoneNumberId = trim(
            (string) $connection->external_sender_id
        );

        if ($phoneNumberId === '') {
            $errors[] =
                'WhatsApp Phone Number ID is missing.';
        } elseif (!ctype_digit($phoneNumberId)) {
            $errors[] =
                'WhatsApp Phone Number ID must be numeric.';
        }

    /*
     * Credentials.
     *
     * Laravel automatically decrypts this because
     * ChannelConnection uses encrypted:array.
     */
    $credentials =
        $connection->credentials;

    if (!is_array($credentials)) {
        $errors[] =
            'WhatsApp credentials are unavailable.';

        return $errors;
    }

    $accessToken = trim(
        (string) (
            $credentials[
                'access_token'
            ] ?? ''
        )
    );

    if ($accessToken === '') {
        $errors[] =
            'WhatsApp access token is missing.';
    }

    return $errors;
}

    /**
     * Throw immediately when the connection is
     * not ready for a provider verification call.
     */
    public function assertConfigurationReady(
        ChannelConnection $connection,
    ): void {
        $errors =
            $this->configurationErrors(
                $connection
            );

        if ($errors === []) {
            return;
        }

        throw new LogicException(
            implode(
                ' ',
                $errors
            )
        );
    }

    /**
     * Safely retrieve the provider access token.
     *
     * Never expose this through controllers/API
     * responses.
     */
    public function accessToken(
        ChannelConnection $connection,
    ): string {
        $this->assertConfigurationReady(
            $connection
        );

        return trim(
            (string)
            $connection->credentials[
                'access_token'
            ]
        );
    }

    /**
     * Ensure callers do not accidentally configure
     * website/facebook/etc connections using this
     * service.
     */
    private function assertWhatsAppConnection(
        ChannelConnection $connection,
    ): void {
        $type = strtolower(
            trim(
                (string) $connection->type
            )
        );

        if ($type !== self::CHANNEL_TYPE) {
            throw new LogicException(
                sprintf(
                    'Expected a whatsapp channel connection; received "%s".',
                    $type !== ''
                        ? $type
                        : 'empty'
                )
            );
        }
    }
}