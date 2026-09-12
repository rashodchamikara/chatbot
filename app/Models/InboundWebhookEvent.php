<?php

namespace App\Services\Omnichannel\WhatsApp;

class WhatsAppWebhookSecurityService
{
    /**
     * Validate Meta webhook GET verification.
     */
    public function validateChallenge(
        string $mode,
        string $providedVerifyToken,
        string $challenge,
    ): bool {
        $configuredVerifyToken = trim(
            (string) config(
                'services.meta.webhook_verify_token'
            )
        );

        if ($configuredVerifyToken === '') {
            return false;
        }

        if (trim($mode) !== 'subscribe') {
            return false;
        }

        if (trim($providedVerifyToken) === '') {
            return false;
        }

        if ($challenge === '') {
            return false;
        }

        return hash_equals(
            $configuredVerifyToken,
            trim($providedVerifyToken)
        );
    }

    /**
     * Validate X-Hub-Signature-256.
     *
     * IMPORTANT:
     * The HMAC must use the exact raw request body.
     */
    public function validateSignature(
        string $rawBody,
        ?string $providedSignature,
    ): bool {
        $appSecret = trim(
            (string) config(
                'services.meta.app_secret'
            )
        );

        if ($appSecret === '') {
            return false;
        }

        $providedSignature = trim(
            (string) $providedSignature
        );

        if ($providedSignature === '') {
            return false;
        }

        if (
            !str_starts_with(
                $providedSignature,
                'sha256='
            )
        ) {
            return false;
        }

        $expectedSignature =
            'sha256=' .
            hash_hmac(
                'sha256',
                $rawBody,
                $appSecret
            );

        return hash_equals(
            $expectedSignature,
            $providedSignature
        );
    }
}