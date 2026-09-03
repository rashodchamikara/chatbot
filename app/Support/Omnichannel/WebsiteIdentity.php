<?php

namespace App\Support\Omnichannel;

use Illuminate\Support\Str;

final class WebsiteIdentity
{
    /**
     * Stable provider-neutral external customer ID.
     *
     * We hash the visitor ID because visitor IDs may be
     * long and provider identity columns should remain
     * predictable in length.
     */
    public static function externalContactId(
        string $visitorId
    ): string {
        $visitorId = trim($visitorId);

        return 'website-visitor:'
            . hash(
                'sha256',
                $visitorId
            );
    }

    /**
     * Stable thread identifier for one website visitor.
     *
     * Same visitor on another website gets another thread.
     */
    public static function externalThreadId(
        int $websiteId,
        string $visitorId
    ): string {
        $visitorId = trim($visitorId);

        return 'website:'
            . $websiteId
            . ':visitor:'
            . hash(
                'sha256',
                $visitorId
            );
    }

    /**
     * External message identifier used for idempotency.
     *
     * Existing widget versions do not need to provide
     * client_message_id. When absent we create a UUID.
     */
    public static function externalMessageId(
        int $channelConnectionId,
        ?string $clientMessageId = null
    ): string {
        $clientMessageId =
            trim(
                (string)
                $clientMessageId
            );

        if ($clientMessageId === '') {
            $clientMessageId =
                (string)
                Str::uuid();
        }

        return 'website:'
            . $channelConnectionId
            . ':message:'
            . $clientMessageId;
    }
}