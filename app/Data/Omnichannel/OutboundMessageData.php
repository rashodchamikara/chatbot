<?php

namespace App\Data\Omnichannel;

class OutboundMessageData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $conversationId,
        public readonly int $channelConnectionId,

        public readonly string $externalContactId,

        public readonly ?string $externalThreadId = null,

        public readonly string $messageType = 'text',
        public readonly ?string $text = null,

        public readonly array $attachments = [],
        public readonly array $metadata = [],
    ) {
    }
}