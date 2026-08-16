<?php

namespace App\Data\Omnichannel;

class SendResult
{
    public function __construct(
        public readonly bool $successful,

        public readonly ?string $externalMessageId = null,

        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,

        public readonly array $metadata = [],
    ) {
    }

    public static function success(
        ?string $externalMessageId = null,
        array $metadata = [],
    ): self {
        return new self(
            successful: true,
            externalMessageId: $externalMessageId,
            metadata: $metadata,
        );
    }

    public static function failure(
        ?string $errorMessage = null,
        ?string $errorCode = null,
        array $metadata = [],
    ): self {
        return new self(
            successful: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }
}