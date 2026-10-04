<?php

namespace App\Services\Omnichannel\WhatsApp;

use App\Data\Omnichannel\WhatsAppConnectionData;
use App\Exceptions\Omnichannel\WhatsAppCloudApiException;
use LogicException;
use App\Models\AiAgent;
use App\Models\ChannelConnection;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MetaEmbeddedSignupService
{
    public function __construct(
        private readonly WhatsAppCloudApiClient $client,
        private readonly WhatsAppConnectionConfigService $configService,
        private readonly WhatsAppConnectionHealthService $healthService,
    ) {
    }

    /**
     * Complete Meta WhatsApp Embedded Signup for one ChatNivo tenant.
     *
     * The browser supplies the temporary OAuth code plus the WABA/phone IDs
     * emitted by Meta's WA_EMBEDDED_SIGNUP FINISH event. We never trust the
     * IDs on their own: the newly exchanged business token must be able to
     * read the phone and the phone must appear inside the supplied WABA.
     */
    public function complete(
        int $tenantId,
        string $authorizationCode,
        string $businessAccountId,
        string $phoneNumberId,
        ?string $businessId = null,
        ?string $connectionName = null,
    ): ChannelConnection {
        $this->assertEmbeddedSignupConfigured();

        $tenant = Tenant::query()->findOrFail($tenantId);

        $authorizationCode = trim($authorizationCode);
        $businessAccountId = trim($businessAccountId);
        $phoneNumberId = trim($phoneNumberId);
        $businessId = $businessId !== null ? trim($businessId) : null;
        $connectionName = $connectionName !== null
            ? trim($connectionName)
            : null;

        $tokenPayload = $this->client->exchangeAuthorizationCode(
            $authorizationCode
        );

        $accessToken = trim((string) ($tokenPayload['access_token'] ?? ''));

        /*
         * Validate the browser-returned assets with the customer-scoped token
         * before persisting anything.
         */
        $wabaPhones = $this->client->phoneNumbersForBusinessAccount(
            businessAccountId: $businessAccountId,
            accessToken: $accessToken,
        );

        $wabaPhone = $this->findPhoneNumber(
            response: $wabaPhones,
            phoneNumberId: $phoneNumberId,
        );

        if ($wabaPhone === null) {
            throw ValidationException::withMessages([
                'phone_number_id' =>
                    'Meta did not confirm that the selected phone number belongs to the selected WhatsApp Business Account.',
            ]);
        }

        $phone = $this->client->phoneNumber(
            phoneNumberId: $phoneNumberId,
            accessToken: $accessToken,
        );

        if (trim((string) ($phone['id'] ?? '')) !== $phoneNumberId) {
            throw ValidationException::withMessages([
                'phone_number_id' =>
                    'Meta returned a different phone number than the one selected during Embedded Signup.',
            ]);
        }

        $providerData = array_merge($wabaPhone, $phone);
        $agent = $this->resolveAgent($tenant);

        $connection = $this->createOrReuseConnection(
            tenantId: $tenant->id,
            agent: $agent,
            businessAccountId: $businessAccountId,
            phoneNumberId: $phoneNumberId,
            requestedName: $connectionName,
            providerData: $providerData,
        );

        $existingCredentials = is_array($connection->credentials)
            ? $connection->credentials
            : [];

        $pin = $this->existingOrNewPin($existingCredentials);

        $extraCredentials = [
            'two_step_pin' => $pin,
            'token_type' => trim((string) ($tokenPayload['token_type'] ?? 'bearer')),
        ];

        $expiresIn = (int) ($tokenPayload['expires_in'] ?? 0);

        if ($expiresIn > 0) {
            $extraCredentials['token_expires_at'] = now()
                ->addSeconds($expiresIn)
                ->toIso8601String();
        }

        $settings = [
            'setup_method' => 'meta_embedded_signup',
            'embedded_signup_version' => 4,
            'embedded_signup_config_id' => trim(
                (string) config('services.meta.embedded_signup_config_id')
            ),
            'embedded_signup_completed_at' => now()->toIso8601String(),
        ];

        if ($businessId !== null && $businessId !== '') {
            $settings['meta_business_id'] = $businessId;
        }

        try {
            $connection = $this->configService->configure(
                connection: $connection,
                data: new WhatsAppConnectionData(
                    accessToken: $accessToken,
                    businessAccountId: $businessAccountId,
                    phoneNumberId: $phoneNumberId,
                    displayPhoneNumber: $this->nullableString(
                        $providerData['display_phone_number'] ?? null
                    ),
                    verifiedName: $this->nullableString(
                        $providerData['verified_name'] ?? null
                    ),
                    settings: $settings,
                    extraCredentials: $extraCredentials,
                ),
            );

            $this->provision($connection, $accessToken);
        } catch (Throwable $exception) {
            report($exception);

            $this->markProvisioningError(
                connection: $connection,
                message: $exception->getMessage(),
                accessToken: $accessToken,
            );
        }

        return $connection->refresh();
    }

    /** Resume a saved signup without reusing its one-time OAuth code. */
    public function retryProvisioning(ChannelConnection $connection): ChannelConnection
    {
        $settings = is_array($connection->settings) ? $connection->settings : [];
        if ($connection->type !== 'whatsapp' || $connection->provider !== 'meta'
            || ($settings['setup_method'] ?? null) !== 'meta_embedded_signup') {
            throw new LogicException('Select a Meta Embedded Signup WhatsApp connection.');
        }

        $accessToken = '';
        try {
            $this->configService->assertConfigurationReady($connection);
            $accessToken = $this->configService->accessToken($connection);
            $phoneId = trim((string) $connection->external_sender_id);
            $phones = $this->client->phoneNumbersForBusinessAccount(
                businessAccountId: trim((string) $connection->external_account_id),
                accessToken: $accessToken,
            );
            if ($this->findPhoneNumber($phones, $phoneId) === null) {
                throw new LogicException('Meta did not confirm this phone belongs to the configured WABA.');
            }
            $phone = $this->client->phoneNumber($phoneId, $accessToken);
            if (trim((string) ($phone['id'] ?? '')) !== $phoneId) {
                throw new LogicException('Meta returned a different WhatsApp Phone Number ID.');
            }

            $connection->forceFill(['status' => 'pending', 'last_error' => null])->save();
            $this->provision($connection, $accessToken);
        } catch (Throwable $exception) {
            report($exception);
            $this->markProvisioningError($connection, $exception->getMessage(), $accessToken);
        }

        return $connection->refresh();
    }

    private function provision(ChannelConnection $connection, string $accessToken): void
    {
        $phoneId = trim((string) $connection->external_sender_id);
        $settings = is_array($connection->settings) ? $connection->settings : [];
        $credentials = is_array($connection->credentials) ? $connection->credentials : [];

        // Only reuse coexistence detection for the exact phone Meta confirmed.
        $coexistence = ($settings['whatsapp_registration_mode'] ?? null) === 'coexistence'
            && ($settings['coexistence_phone_number_id'] ?? null) === $phoneId;

        if (!$coexistence) {
            $pin = $this->existingOrNewPin($credentials);
            $credentials['two_step_pin'] = $pin;
            $connection->forceFill(['credentials' => $credentials])->save();

            try {
                $result = $this->client->registerPhone($phoneId, $accessToken, $pin);
                if (($result['success'] ?? null) !== true) {
                    throw new LogicException('Meta did not confirm WhatsApp phone registration.');
                }
            } catch (WhatsAppCloudApiException $exception) {
                // This precise Meta response identifies an SMB/Business App number.
                // Never ignore other code-100 errors, token failures or permissions errors.
                if (!$this->isSmbRegistrationResponse($exception)) {
                    throw $exception;
                }
                $coexistence = true;
            }
        }

        if ($coexistence) {
            // The generated PIN was not installed on the Business App number.
            unset($credentials['two_step_pin']);
            $settings['whatsapp_registration_mode'] = 'coexistence';
            $settings['coexistence_phone_number_id'] = $phoneId;
        } else {
            $settings['whatsapp_registration_mode'] = 'cloud_api';
            unset($settings['coexistence_phone_number_id']);
        }
        $connection->forceFill(['credentials' => $credentials, 'settings' => $settings])->save();

        // SMB registration is skipped, but webhook setup remains mandatory.
        $subscription = $this->client->subscribeAppToBusinessAccount(
            businessAccountId: trim((string) $connection->external_account_id),
            accessToken: $accessToken,
        );
        if (($subscription['success'] ?? null) !== true) {
            throw new LogicException('Meta did not confirm the WABA webhook subscription.');
        }
        $settings['webhook_subscribed_at'] = now()->toIso8601String();
        $connection->forceFill(['settings' => $settings])->save();

        // Existing check verifies token access and phone/WABA membership before activation.
        // Sending and receiving a real message still needs to be tested after deployment.
        $this->healthService->check($connection);
    }

    private function isSmbRegistrationResponse(WhatsAppCloudApiException $exception): bool
    {
        $message = strtolower(rtrim(trim($exception->getMessage()), '.'));
        return $exception->httpStatus === 400
            && $exception->metaErrorCode === '100'
            && $message === 'register endpoint is not available for smb businesses';
    }

    private function assertEmbeddedSignupConfigured(): void
    {
        $required = [
            'META_APP_ID' => config('services.meta.app_id'),
            'META_APP_SECRET' => config('services.meta.app_secret'),
            'META_EMBEDDED_SIGNUP_CONFIG_ID' =>
                config('services.meta.embedded_signup_config_id'),
        ];

        $missing = [];

        foreach ($required as $label => $value) {
            if (trim((string) $value) === '') {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'meta_configuration' =>
                    'Meta Embedded Signup is not fully configured. Missing: '
                    . implode(', ', $missing) . '.',
            ]);
        }
    }

    private function findPhoneNumber(
        array $response,
        string $phoneNumberId,
    ): ?array {
        $data = $response['data'] ?? null;

        if (!is_array($data)) {
            return null;
        }

        foreach ($data as $phone) {
            if (!is_array($phone)) {
                continue;
            }

            if (trim((string) ($phone['id'] ?? '')) === $phoneNumberId) {
                return $phone;
            }
        }

        return null;
    }

    private function createOrReuseConnection(
        int $tenantId,
        AiAgent $agent,
        string $businessAccountId,
        string $phoneNumberId,
        ?string $requestedName,
        array $providerData,
    ): ChannelConnection {
        return DB::transaction(function () use (
            $tenantId,
            $agent,
            $businessAccountId,
            $phoneNumberId,
            $requestedName,
            $providerData,
        ): ChannelConnection {
            $existing = ChannelConnection::query()
                ->where('type', 'whatsapp')
                ->where('provider', 'meta')
                ->where('external_sender_id', $phoneNumberId)
                ->lockForUpdate()
                ->first();

            if ($existing && (int) $existing->tenant_id !== $tenantId) {
                throw ValidationException::withMessages([
                    'phone_number_id' =>
                        'This WhatsApp phone number is already connected to another tenant in ChatNivo.',
                ]);
            }

            $verifiedName = trim((string) ($providerData['verified_name'] ?? ''));
            $displayPhone = trim((string) ($providerData['display_phone_number'] ?? ''));

            $name = trim((string) $requestedName);

            if ($name === '' && $existing) {
                $name = trim((string) $existing->name);
            }

            if ($name === '') {
                $name = $verifiedName !== ''
                    ? $verifiedName
                    : ($displayPhone !== '' ? 'WhatsApp ' . $displayPhone : 'WhatsApp');
            }

            if (!$existing) {
                return ChannelConnection::query()->create([
                    'tenant_id' => $tenantId,
                    'ai_agent_id' => $agent->id,
                    'website_id' => null,
                    'type' => 'whatsapp',
                    'provider' => 'meta',
                    'name' => $name,
                    'status' => 'pending',
                    'external_account_id' => $businessAccountId,
                    'external_sender_id' => $phoneNumberId,
                    'webhook_key' => (string) Str::ulid(),
                    'settings' => [
                        'setup_method' => 'meta_embedded_signup',
                    ],
                ]);
            }

            $agentBelongsToTenant = $existing->ai_agent_id
                && AiAgent::query()
                    ->whereKey($existing->ai_agent_id)
                    ->where('tenant_id', $tenantId)
                    ->exists();

            $existing->forceFill([
                'ai_agent_id' => $agentBelongsToTenant
                    ? $existing->ai_agent_id
                    : $agent->id,
                'website_id' => null,
                'name' => $name,
                'external_account_id' => $businessAccountId,
                'external_sender_id' => $phoneNumberId,
                'status' => 'pending',
                'last_error' => null,
            ])->save();

            return $existing->refresh();
        });
    }

    private function resolveAgent(Tenant $tenant): AiAgent
    {
        $agent = AiAgent::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if ($agent) {
            return $agent;
        }

        $businessName = trim(
            (string) ($tenant->company_name ?: $tenant->name)
        );

        return AiAgent::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ($businessName !== '' ? $businessName : 'Business')
                . ' AI Assistant',
            'status' => 'active',
            'instructions' => null,
            'default_language' => 'en',
            'model_settings' => [],
            'handover_settings' => [
                'enabled' => true,
            ],
            'business_hours' => [],
        ]);
    }

    private function existingOrNewPin(array $credentials): string
    {
        $existing = trim((string) ($credentials['two_step_pin'] ?? ''));

        if (preg_match('/^\d{6}$/', $existing)) {
            return $existing;
        }

        return (string) random_int(100000, 999999);
    }

    private function markProvisioningError(
        ChannelConnection $connection,
        string $message,
        string $accessToken,
    ): void {
        $message = trim($message);

        if ($message === '') {
            $message = 'Meta Embedded Signup provisioning failed.';
        }

        if ($accessToken !== '') {
            $message = str_replace($accessToken, '[REDACTED]', $message);
        }

        $connection->forceFill([
            'status' => 'error',
            'last_error' => Str::limit($message, 2000, ''),
            'last_health_check_at' => now(),
        ])->save();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
