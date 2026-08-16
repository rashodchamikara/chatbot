<?php

namespace App\Data\Omnichannel;

class InboundMessageData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $channelConnectionId,

        public readonly string $externalContactId,
        public readonly string $externalMessageId,

        public readonly ?string $externalThreadId = null,

        public readonly ?string $contactName = null,
        public readonly ?string $contactEmail = null,
        public readonly ?string $contactPhone = null,

        public readonly string $messageType = 'text',
        public readonly ?string $text = null,

        public readonly array $attachments = [],
        public readonly array $metadata = [],
    ) {
    }
}