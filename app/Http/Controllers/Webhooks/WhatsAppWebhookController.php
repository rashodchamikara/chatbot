<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\InboundWebhookEvent;
use App\Services\Omnichannel\WhatsApp\WhatsAppWebhookSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly
            WhatsAppWebhookSecurityService
            $securityService,
    ) {
    }

    /**
     * Meta callback verification.
     */
    public function verify(
        Request $request
    ): Response {
        $mode =
            $this->queryValue(
                request:
                    $request,

                dotted:
                    'hub.mode',

                underscored:
                    'hub_mode',
            );

        $verifyToken =
            $this->queryValue(
                request:
                    $request,

                dotted:
                    'hub.verify_token',

                underscored:
                    'hub_verify_token',
            );

        $challenge =
            $this->queryValue(
                request:
                    $request,

                dotted:
                    'hub.challenge',

                underscored:
                    'hub_challenge',
            );

        $valid =
            $this->securityService
                ->validateChallenge(
                    mode:
                        $mode,

                    providedVerifyToken:
                        $verifyToken,

                    challenge:
                        $challenge,
                );

        if (!$valid) {
            return response(
                'Forbidden',
                403
            );
        }

        /*
         * Meta expects the raw challenge string.
         */
        return response(
            $challenge,
            200,
            [
                'Content-Type' =>
                    'text/plain',
            ]
        );
    }

    /**
     * Receive Meta webhook notifications.
     */
    public function receive(
        Request $request
    ): JsonResponse {
        /*
         * Use raw request bytes for HMAC.
         */
        $rawBody =
            $request->getContent();

        $signature =
            $request->header(
                'X-Hub-Signature-256'
            );

        if (
            !$this->securityService
                ->validateSignature(
                    rawBody:
                        $rawBody,

                    providedSignature:
                        $signature,
                )
        ) {
            return response()->json(
                [
                    'received' =>
                        false,

                    'message' =>
                        'Invalid webhook signature.',
                ],
                401
            );
        }

        try {

            $payload =
                json_decode(
                    $rawBody,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

        } catch (JsonException) {

            return response()->json(
                [
                    'received' =>
                        false,

                    'message' =>
                        'Invalid JSON payload.',
                ],
                400
            );
        }

        if (!is_array($payload)) {
            return response()->json(
                [
                    'received' =>
                        false,
                ],
                400
            );
        }

        if (
            ($payload['object'] ?? null)
            !== 'whatsapp_business_account'
        ) {
            return response()->json([
                'received' =>
                    true,

                'ignored' =>
                    true,
            ]);
        }

        /*
         * Hash exact Meta body for idempotency.
         */
        $payloadHash =
            hash(
                'sha256',
                $rawBody
            );

        $event =
            InboundWebhookEvent::query()
                ->firstOrCreate(
                    [
                        'provider' =>
                            'meta',

                        'payload_hash' =>
                            $payloadHash,
                    ],
                    [
                        'event_type' =>
                            $this->eventType(
                                $payload
                            ),

                        'external_event_id' =>
                            $this->externalEventId(
                                $payload
                            ),

                        'payload' =>
                            $rawBody,

                        'headers' => [
                            'content_type' =>
                                $request->header(
                                    'Content-Type'
                                ),

                            'user_agent' =>
                                $request->userAgent(),
                        ],

                        'status' =>
                            'received',

                        'attempts' =>
                            0,

                        'received_at' =>
                            now(),

                        'metadata' => [
                            'source' =>
                                'meta_whatsapp',
                        ],
                    ]
                );

        /*
         * Duplicate Meta requests shouldn't create
         * duplicate processing jobs after success.
         */
        if (
            !in_array(
                $event->status,
                [
                    'processing',
                    'processed',
                ],
                true
            )
        ) {
            ProcessWhatsAppWebhookJob::dispatch(
                (int) $event->id
            );
        }

        /*
         * Acknowledge quickly.
         */
        return response()->json([
            'received' =>
                true,

            'event_id' =>
                $event->id,

            'duplicate' =>
                !$event
                    ->wasRecentlyCreated,
        ]);
    }

    private function queryValue(
        Request $request,
        string $dotted,
        string $underscored,
    ): string {
        return trim(
            (string) (
                $request->query(
                    $dotted
                )
                ??
                $request->query(
                    $underscored
                )
                ?? ''
            )
        );
    }

    private function eventType(
        array $payload
    ): ?string {
        foreach (
            $payload['entry'] ?? []
            as $entry
        ) {
            foreach (
                $entry['changes'] ?? []
                as $change
            ) {
                $field =
                    trim(
                        (string) (
                            $change['field']
                            ?? ''
                        )
                    );

                if ($field !== '') {
                    return $field;
                }
            }
        }

        return null;
    }

    private function externalEventId(
        array $payload
    ): ?string {
        foreach (
            $payload['entry'] ?? []
            as $entry
        ) {
            foreach (
                $entry['changes'] ?? []
                as $change
            ) {
                $value =
                    $change['value']
                    ?? [];

                if (
                    isset(
                        $value[
                            'messages'
                        ][0]['id']
                    )
                ) {
                    return trim(
                        (string)
                        $value[
                            'messages'
                        ][0]['id']
                    );
                }

                if (
                    isset(
                        $value[
                            'statuses'
                        ][0]['id']
                    )
                ) {
                    return trim(
                        (string)
                        $value[
                            'statuses'
                        ][0]['id']
                    );
                }
            }
        }

        return null;
    }
}