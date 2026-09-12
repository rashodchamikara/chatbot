<?php

namespace App\Data\Omnichannel;

use App\Models\ChannelConnection;

final readonly class WhatsAppConnectionHealthResult
{
    public function __construct(
        public ChannelConnection $connection,
        public bool $healthy,
        public string $message,
        public array $providerData = [],
    ) {
    }

    public static function healthy(
        ChannelConnection $connection,
        array $providerData = [],
    ): self {
        return new self(
            connection:
                $connection,

            healthy:
                true,

            message:
                'WhatsApp connection verified successfully.',

            providerData:
                $providerData,
        );
    }

    public static function unhealthy(
        ChannelConnection $connection,
        string $message,
    ): self {
        return new self(
            connection:
                $connection,

            healthy:
                false,

            message:
                $message,

            providerData:
                [],
        );
    }
}