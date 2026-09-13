<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Website;
use App\Services\Knowledge\KnowledgeContextBuilder;
use App\Services\Knowledge\KnowledgeRetriever;
use App\Services\LeadCaptureService;
use App\Services\Omnichannel\OutboundMessageService;
use App\Services\SalesBrainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateOmnichannelAiReplyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Retry transient OpenAI / provider failures.
     */
    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Prevent duplicate jobs for the same inbound provider message while one
     * copy is already queued/running.
     */
    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $inboundMessageId,
    ) {
    }

    public function uniqueId(): string
    {
        return (string) $this->inboundMessageId;
    }

    public function backoff(): array
    {
        return [
            10,
            30,
            60,
        ];
    }

    public function handle(
        KnowledgeRetriever $knowledgeRetriever,
        KnowledgeContextBuilder $contextBuilder,
        SalesBrainService $brain,
        LeadCaptureService $leadCaptureService,
        OutboundMessageService $outboundMessageService,
    ): void {
        $inboundMessage = Message::query()
            ->with([
                'conversation.channelConnection',
                'conversation.website',
                'conversation.lead',
            ])
            ->find($this->inboundMessageId);

        if (!$inboundMessage) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Only respond to genuine inbound contact messages
        |--------------------------------------------------------------------------
        */
        if (
            $inboundMessage->direction !== 'inbound'
            || !in_array(
                $inboundMessage->sender_type,
                ['contact', null],
                true
            )
        ) {
            return;
        }

        $conversation = $inboundMessage->conversation;

        if (!$conversation) {
            throw new RuntimeException(
                'Inbound message does not belong to a conversation.'
            );
        }

        $conversation->refresh();
        $conversation->loadMissing([
            'channelConnection',
            'website',
            'lead',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Human takeover always wins
        |--------------------------------------------------------------------------
        |
        | Website chat already uses this rule. Apply the same rule to WhatsApp.
        | If an agent has been requested or is already active, do not generate AI.
        |
        */
        if (
            in_array(
                $conversation->mode,
                ['live_waiting', 'live'],
                true
            )
        ) {
            Log::info(
                'Omnichannel AI reply skipped because conversation is in live-agent mode.',
                [
                    'conversation_id' => $conversation->id,
                    'inbound_message_id' => $inboundMessage->id,
                    'mode' => $conversation->mode,
                ]
            );

            return;
        }

        if ($conversation->mode !== 'ai') {
            Log::info(
                'Omnichannel AI reply skipped because conversation mode is not AI.',
                [
                    'conversation_id' => $conversation->id,
                    'inbound_message_id' => $inboundMessage->id,
                    'mode' => $conversation->mode,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Idempotency
        |--------------------------------------------------------------------------
        |
        | Meta can retry webhooks and a webhook job itself can also be retried.
        | Never send a second successful AI reply for the same inbound message.
        |
        */
        if (
            $this->alreadyHasAiReply(
                conversationId: (int) $conversation->id,
                inboundMessageId: (int) $inboundMessage->id,
            )
        ) {
            return;
        }

        $connection = $conversation->channelConnection;

        if (!$connection) {
            throw new RuntimeException(
                'Conversation does not have a channel connection.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve website knowledge / chatbot configuration
        |--------------------------------------------------------------------------
        |
        | The existing SalesBrainService is website-centric. WhatsApp connections
        | are currently linked to a website_id, allowing the same indexed knowledge,
        | chatbot name and chatbot instructions to be reused across channels.
        |
        */
        $website = $conversation->website;

        if (!$website && $connection->website_id) {
            $website = Website::query()
                ->whereKey($connection->website_id)
                ->where('tenant_id', $conversation->tenant_id)
                ->first();
        }

        if (!$website) {
            throw new RuntimeException(
                'WhatsApp channel connection is not linked to a website knowledge source.'
            );
        }

        if (
            (int) $website->tenant_id
            !== (int) $conversation->tenant_id
        ) {
            throw new RuntimeException(
                'Website and WhatsApp conversation belong to different tenants.'
            );
        }

        $messageText = trim(
            (string) $inboundMessage->message
        );

        if ($messageText === '') {
            Log::info(
                'Omnichannel AI reply skipped because inbound message has no text.',
                [
                    'conversation_id' => $conversation->id,
                    'inbound_message_id' => $inboundMessage->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Build conversation history BEFORE the current inbound message
        |--------------------------------------------------------------------------
        |
        | SalesBrainService appends the current message itself. Including it here
        | would duplicate the customer's latest WhatsApp message in the prompt.
        |
        */
        $history = $this->buildHistory(
            conversation: $conversation,
            beforeMessageId: (int) $inboundMessage->id,
        );

        /*
        |--------------------------------------------------------------------------
        | Lead capture
        |--------------------------------------------------------------------------
        |
        | Lead extraction is useful but should not prevent a WhatsApp AI response
        | if that secondary OpenAI call temporarily fails.
        |
        */
        $lead = $conversation->lead;
        $leadStage = $conversation->lead_stage
            ?: 'discovery';
        $nextLeadQuestion = null;

        try {
            $leadResult = $leadCaptureService->processMessage(
                $website,
                $conversation,
                $messageText
            );

            $lead = $leadResult['lead']
                ?? $lead;

            $leadStage = $leadResult['lead_stage']
                ?? $conversation->lead_stage
                ?? 'discovery';

            $nextLeadQuestion = $leadResult['next_question']
                ?? null;
        } catch (Throwable $exception) {
            Log::warning(
                'WhatsApp lead capture failed; AI reply will continue.',
                [
                    'website_id' => $website->id,
                    'conversation_id' => $conversation->id,
                    'inbound_message_id' => $inboundMessage->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Knowledge retrieval
        |--------------------------------------------------------------------------
        */
        $knowledgeContext =
            'No relevant knowledge was found for this question.';

        try {
            $knowledgeResults = $knowledgeRetriever->retrieve(
                $website,
                $messageText
            );

            $knowledgeContext = $contextBuilder->build(
                $knowledgeResults
            );
        } catch (Throwable $exception) {
            Log::warning(
                'WhatsApp knowledge retrieval failed; AI reply will continue.',
                [
                    'website_id' => $website->id,
                    'conversation_id' => $conversation->id,
                    'inbound_message_id' => $inboundMessage->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Generate AI response
        |--------------------------------------------------------------------------
        */
        try {
            $aiText = $brain->analyze(
                $messageText,
                $website,
                $history,
                $lead,
                $leadStage,
                $nextLeadQuestion,
                $knowledgeContext
            );
        } catch (Throwable $exception) {
            Log::error(
                'WhatsApp AI response generation failed.',
                [
                    'website_id' => $website->id,
                    'conversation_id' => $conversation->id,
                    'inbound_message_id' => $inboundMessage->id,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }

        $aiText = trim(
            (string) $aiText
        );

        if ($aiText === '') {
            $aiText =
                'Sorry, I could not generate a response right now.';
        }

        /*
        |--------------------------------------------------------------------------
        | Re-check mode immediately before sending
        |--------------------------------------------------------------------------
        |
        | An agent may have taken over while embeddings/OpenAI were running.
        |
        */
        $conversation->refresh();

        if (
            $conversation->mode !== 'ai'
            || $this->alreadyHasAiReply(
                conversationId: (int) $conversation->id,
                inboundMessageId: (int) $inboundMessage->id,
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Deliver through the common omnichannel outbound service
        |--------------------------------------------------------------------------
        |
        | For WhatsApp this reaches WhatsAppAdapter -> WhatsAppCloudApiClient.
        |
        */
        try {
            $outboundMessageService->send(
                conversation: $conversation,
                body: $aiText,
                senderType: 'ai',
                senderUserId: null,
                isAiGenerated: true,
                attachments: [],
                metadata: [
                    'source' => 'whatsapp_ai',
                    'in_reply_to_message_id' => (int) $inboundMessage->id,
                    'in_reply_to_external_message_id' =>
                        $inboundMessage->external_message_id,
                ],
            );
        } catch (Throwable $exception) {
            Log::error(
                'WhatsApp AI outbound delivery failed.',
                [
                    'website_id' => $website->id,
                    'conversation_id' => $conversation->id,
                    'inbound_message_id' => $inboundMessage->id,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    private function buildHistory(
        Conversation $conversation,
        int $beforeMessageId,
    ): array {
        return Message::query()
            ->where(
                'conversation_id',
                $conversation->id
            )
            ->where(
                'id',
                '<',
                $beforeMessageId
            )
            ->where(
                function ($query): void {
                    $query
                        ->where(
                            'is_system',
                            false
                        )
                        ->orWhereNull(
                            'is_system'
                        );
                }
            )
            ->where(
                function ($query): void {
                    $query
                        ->whereIn(
                            'sender',
                            [
                                'visitor',
                                'user',
                                'ai',
                                'assistant',
                                'agent',
                            ]
                        )
                        ->orWhereIn(
                            'sender_type',
                            [
                                'contact',
                                'ai',
                                'agent',
                            ]
                        );
                }
            )
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(
                function (Message $message): array {
                    $isVisitor =
                        $message->direction === 'inbound'
                        || $message->sender_type === 'contact'
                        || in_array(
                            $message->sender,
                            ['visitor', 'user'],
                            true
                        );

                    return [
                        'role' => $isVisitor
                            ? 'user'
                            : 'assistant',

                        'content' =>
                            (string) $message->message,
                    ];
                }
            )
            ->values()
            ->toArray();
    }

    private function alreadyHasAiReply(
        int $conversationId,
        int $inboundMessageId,
    ): bool {
        $candidateReplies = Message::query()
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'id',
                '>',
                $inboundMessageId
            )
            ->where(
                'is_ai_generated',
                true
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->latest('id')
            ->limit(20)
            ->get([
                'id',
                'payload',
                'status',
                'created_at',
            ]);

        foreach ($candidateReplies as $reply) {
            $payload = is_array($reply->payload)
                ? $reply->payload
                : [];

            $metadata = is_array(
                $payload['metadata'] ?? null
            )
                ? $payload['metadata']
                : [];

            if (
                (int) (
                    $metadata['in_reply_to_message_id']
                    ?? 0
                ) !== $inboundMessageId
            ) {
                continue;
            }

            if (
                in_array(
                    $reply->status,
                    ['sent', 'delivered', 'read'],
                    true
                )
            ) {
                return true;
            }

            /*
             * A very recent pending record normally means another worker is
             * currently delivering this same AI response. Old orphaned pending
             * records should not block retries forever.
             */
            if (
                $reply->status === 'pending'
                && $reply->created_at
                && $reply->created_at->gt(
                    now()->subMinutes(2)
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
