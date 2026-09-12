<?php

namespace App\Data\Omnichannel;

class SendResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $externalMessageId = null,
        public readonly string $status = 'sent',
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = [],
        public readonly array $metadata = [],
    ) {
    }

    public static function success(
        ?string $externalMessageId = null,
        string $status = 'sent',
        array $metadata = [],
        array $rawResponse = [],
    ): self {
        return new self(
            successful: true,
            externalMessageId: $externalMessageId,
            status: $status,
            errorCode: null,
            errorMessage: null,
            rawResponse: $rawResponse,
            metadata: $metadata,
        );
    }

    public static function failure(
        ?string $errorMessage = null,
        ?string $errorCode = null,
        string $status = 'failed',
        array $metadata = [],
        array $rawResponse = [],
    ): self {
        return new self(
            successful: false,
            externalMessageId: null,
            status: $status,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
            metadata: $metadata,
        );
    }
}