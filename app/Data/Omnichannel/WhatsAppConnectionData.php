<?php

namespace App\Data\Omnichannel;

final readonly class WhatsAppConnectionData
{
    public function __construct(
        public string $accessToken,
        public string $businessAccountId,
        public string $phoneNumberId,
        public ?string $displayPhoneNumber = null,
        public ?string $verifiedName = null,
        public array $settings = [],
        public array $extraCredentials = [],
    ) {
    }

    /**
     * Sensitive provider credentials.
     *
     * ChannelConnection encrypts this entire array through its
     * encrypted:array cast. Never copy these values into settings.
     */
    public function credentials(): array
    {
        return array_merge(
            $this->extraCredentials,
            [
                'access_token' => trim($this->accessToken),
            ],
        );
    }

    /**
     * Non-sensitive provider/channel metadata.
     */
    public function connectionSettings(): array
    {
        $settings = $this->settings;

        if (
            $this->displayPhoneNumber !== null
            && trim($this->displayPhoneNumber) !== ''
        ) {
            $settings['display_phone_number'] =
                trim($this->displayPhoneNumber);
        }

        if (
            $this->verifiedName !== null
            && trim($this->verifiedName) !== ''
        ) {
            $settings['verified_name'] =
                trim($this->verifiedName);
        }

        return $settings;
    }
}
