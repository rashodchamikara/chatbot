<?php

namespace App\Jobs;

use App\Models\ChannelConnection;
use App\Models\InboundWebhookEvent;
use App\Services\Omnichannel\Adapters\WhatsAppAdapter;
use App\Services\Omnichannel\InboundMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(
        public readonly int $webhookEventId,
    ) {
    }

    public function backoff(): array
    {
        return [
            10,
            30,
            60,
            300,
        ];
    }

    public function handle(
        WhatsAppAdapter $adapter,
        InboundMessageService $inboundMessageService,
    ): void {
        $event = InboundWebhookEvent::query()
            ->find(
                $this->webhookEventId
            );

        if (!$event) {
            return;
        }

        if ($event->status === 'processed') {
            return;
        }

        $event->forceFill([
            'status' => 'processing',

            'attempts' =>
                ((int) $event->attempts)
                + 1,

            'processing_started_at' => now(),

            'failed_at' => null,

            'last_error' => null,
        ])->save();

        try {
            $payload = json_decode(
                $event->payload,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($payload)) {
                throw new RuntimeException(
                    'WhatsApp webhook payload is invalid.'
                );
            }

            $processedMessages = 0;
            $aiReplyJobsDispatched = 0;
            $matchedConnectionIds = [];
            $phoneNumberIds = [];

            $entries = $payload['entry']
                ?? [];

            if (!is_array($entries)) {
                $entries = [];
            }

            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $wabaId = trim(
                    (string) (
                        $entry['id']
                        ?? ''
                    )
                );

                $changes = $entry['changes']
                    ?? [];

                if (!is_array($changes)) {
                    continue;
                }

                foreach ($changes as $change) {
                    if (!is_array($change)) {
                        continue;
                    }

                    if (
                        ($change['field'] ?? null)
                        !== 'messages'
                    ) {
                        continue;
                    }

                    $value = $change['value']
                        ?? [];

                    if (!is_array($value)) {
                        continue;
                    }

                    $phoneNumberId = trim(
                        (string) (
                            $value['metadata']['phone_number_id']
                            ?? ''
                        )
                    );

                    if ($phoneNumberId === '') {
                        continue;
                    }

                    $phoneNumberIds[] =
                        $phoneNumberId;

                    /*
                     * Resolve the correct tenant connection.
                     */
                    $query = ChannelConnection::query()
                        ->where(
                            'type',
                            'whatsapp'
                        )
                        ->where(
                            'provider',
                            'meta'
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->where(
                            'external_sender_id',
                            $phoneNumberId
                        );

                    if ($wabaId !== '') {
                        $query->where(
                            'external_account_id',
                            $wabaId
                        );
                    }

                    $connection = $query->first();

                    if (!$connection) {
                        continue;
                    }

                    $matchedConnectionIds[] =
                        (int) $connection->id;

                    /*
                     * Scope the parser to exactly this connection/change.
                     */
                    $scopedPayload = [
                        'object' =>
                            'whatsapp_business_account',

                        'entry' => [
                            [
                                'id' => $wabaId,

                                'changes' => [
                                    $change,
                                ],
                            ],
                        ],
                    ];

                    $messages = $adapter
                        ->parseInboundPayload(
                            connection: $connection,
                            payload: $scopedPayload,
                        );

                    foreach (
                        $messages
                        as $messageData
                    ) {
                        /*
                         * Persist the normalized WhatsApp message first.
                         * InboundMessageService handles provider-message
                         * idempotency using external_message_id.
                         */
                        $inboundMessage =
                            $inboundMessageService->handle(
                                $messageData
                            );

                        $processedMessages++;

                        /*
                         * Automatic AI processing is deliberately separated
                         * from webhook persistence. The webhook job can finish
                         * quickly and OpenAI/Meta delivery can retry independently.
                         *
                         * The AI job itself checks conversation mode, so no AI
                         * reply is sent after a human takes over.
                         */
                        GenerateOmnichannelAiReplyJob::dispatch(
                            (int) $inboundMessage->id
                        );

                        $aiReplyJobsDispatched++;
                    }

                    $connection->forceFill([
                        'last_webhook_at' => now(),
                    ])->save();

                    if (
                        !$event->channel_connection_id
                    ) {
                        $event->channel_connection_id =
                            $connection->id;
                    }
                }
            }

            $metadata = is_array(
                $event->metadata
            )
                ? $event->metadata
                : [];

            $metadata['processed_messages'] =
                $processedMessages;

            $metadata['ai_reply_jobs_dispatched'] =
                $aiReplyJobsDispatched;

            $metadata['phone_number_ids'] =
                array_values(
                    array_unique(
                        $phoneNumberIds
                    )
                );

            $metadata['matched_connection_ids'] =
                array_values(
                    array_unique(
                        $matchedConnectionIds
                    )
                );

            $event->forceFill([
                'status' => 'processed',

                'processed_at' => now(),

                'failed_at' => null,

                'last_error' => null,

                'metadata' => $metadata,
            ])->save();
        } catch (Throwable $exception) {
            $event->forceFill([
                'status' => 'failed',

                'failed_at' => now(),

                'last_error' => Str::limit(
                    $exception->getMessage(),
                    2000,
                    ''
                ),
            ])->save();

            throw $exception;
        }
    }
}
