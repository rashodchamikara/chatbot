<?php

namespace App\Exceptions\Omnichannel;

use RuntimeException;
use Throwable;

class WhatsAppCloudApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $metaErrorCode = null,
        public readonly ?string $metaErrorSubcode = null,
        public readonly ?string $metaTraceId = null,
        public readonly array $responseData = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            0,
            $previous
        );
    }
}