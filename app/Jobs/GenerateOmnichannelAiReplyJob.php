<?php

namespace App\Jobs;

use App\Models\AiAgent;
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

    public int $tries = 3;
    public int $timeout = 120;
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
        return [10, 30, 60];
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
                'conversation.channelConnection.aiAgent.tenant',
                'conversation.aiAgent.tenant',
                'conversation.website.aiAgent',
                'conversation.lead',
            ])
            ->find($this->inboundMessageId);

        if (!$inboundMessage) {
            return;
        }

        if (
            $inboundMessage->direction !== 'inbound'
            || !in_array($inboundMessage->sender_type, ['contact', null], true)
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
            'channelConnection.aiAgent.tenant',
            'aiAgent.tenant',
            'website.aiAgent',
            'lead',
        ]);

        if (in_array($conversation->mode, ['live_waiting', 'live'], true)) {
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
            return;
        }

        if ($this->alreadyHasAiReply(
            conversationId: (int) $conversation->id,
            inboundMessageId: (int) $inboundMessage->id,
        )) {
            return;
        }

        $connection = $conversation->channelConnection;

        if (!$connection) {
            throw new RuntimeException(
                'Conversation does not have a channel connection.'
            );
        }

        $website = $this->resolveOptionalWebsite(
            $conversation,
            $connection->website_id
        );

        $agent = $this->resolveAgent(
            $conversation,
            $connection->aiAgent,
            $website
        );

        if ((int) $agent->tenant_id !== (int) $conversation->tenant_id) {
            throw new RuntimeException(
                'AI agent and conversation belong to different tenants.'
            );
        }

        $messageText = trim((string) $inboundMessage->message);

        if ($messageText === '') {
            return;
        }

        $history = $this->buildHistory(
            conversation: $conversation,
            beforeMessageId: (int) $inboundMessage->id,
        );

        $lead = $conversation->lead;
        $leadStage = $conversation->lead_stage ?: 'discovery';
        $nextLeadQuestion = null;

        try {
            $leadResult = $leadCaptureService->processOmnichannelMessage(
                conversation: $conversation,
                message: $messageText,
                website: $website,
            );

            $lead = $leadResult['lead'] ?? $lead;
            $leadStage = $leadResult['lead_stage']
                ?? $conversation->lead_stage
                ?? 'discovery';
            $nextLeadQuestion = $leadResult['next_question'] ?? null;
        } catch (Throwable $exception) {
            Log::warning(
                'Omnichannel lead capture failed; AI reply will continue.',
                [
                    'tenant_id' => $conversation->tenant_id,
                    'ai_agent_id' => $agent->id,
                    'channel_connection_id' => $connection->id,
                    'conversation_id' => $conversation->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }

        $knowledgeContext = 'No relevant trained knowledge was found for this question.';

        try {
            $knowledgeResults = $knowledgeRetriever->retrieveForAgent(
                agent: $agent,
                question: $messageText,
            );

            $knowledgeContext = $contextBuilder->build($knowledgeResults);
        } catch (Throwable $exception) {
            Log::warning(
                'Omnichannel knowledge retrieval failed; AI reply will continue.',
                [
                    'tenant_id' => $conversation->tenant_id,
                    'ai_agent_id' => $agent->id,
                    'channel_connection_id' => $connection->id,
                    'conversation_id' => $conversation->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }

        try {
            $aiText = $brain->analyzeForAgent(
                message: $messageText,
                agent: $agent,
                history: $history,
                lead: $lead,
                leadStage: $leadStage,
                nextLeadQuestion: $nextLeadQuestion,
                knowledgeContext: $knowledgeContext,
                website: $website,
                channelType: $connection->type,
            );
        } catch (Throwable $exception) {
            Log::error(
                'Omnichannel AI response generation failed.',
                [
                    'tenant_id' => $conversation->tenant_id,
                    'ai_agent_id' => $agent->id,
                    'channel_connection_id' => $connection->id,
                    'conversation_id' => $conversation->id,
                    'error' => $exception->getMessage(),
                ]
            );
            throw $exception;
        }

        $aiText = trim((string) $aiText);

        if ($aiText === '') {
            $aiText = 'Sorry, I could not generate a response right now.';
        }

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

        $outboundMessageService->send(
            conversation: $conversation,
            body: $aiText,
            senderType: 'ai',
            senderUserId: null,
            isAiGenerated: true,
            attachments: [],
            metadata: [
                'source' => 'omnichannel_ai',
                'channel' => $connection->type,
                'in_reply_to_message_id' => (int) $inboundMessage->id,
                'in_reply_to_external_message_id' => $inboundMessage->external_message_id,
            ],
        );
    }

    private function resolveOptionalWebsite(
        Conversation $conversation,
        ?int $connectionWebsiteId,
    ): ?Website {
        $website = $conversation->website;

        if (!$website && $connectionWebsiteId) {
            $website = Website::query()
                ->whereKey($connectionWebsiteId)
                ->where('tenant_id', $conversation->tenant_id)
                ->first();
        }

        if (
            $website
            && (int) $website->tenant_id !== (int) $conversation->tenant_id
        ) {
            throw new RuntimeException(
                'Website and conversation belong to different tenants.'
            );
        }

        return $website;
    }

    private function resolveAgent(
        Conversation $conversation,
        ?AiAgent $connectionAgent,
        ?Website $website,
    ): AiAgent {
        $agent = $conversation->aiAgent
            ?: $connectionAgent
            ?: $website?->aiAgent;

        if (!$agent) {
            throw new RuntimeException(
                'Conversation is not linked to an AI agent. Configure an AI agent for this channel.'
            );
        }

        return $agent;
    }

    private function buildHistory(
        Conversation $conversation,
        int $beforeMessageId,
    ): array {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', '<', $beforeMessageId)
            ->where(function ($query): void {
                $query->where('is_system', false)
                    ->orWhereNull('is_system');
            })
            ->where(function ($query): void {
                $query->whereIn(
                    'sender',
                    ['visitor', 'user', 'ai', 'assistant', 'agent']
                )->orWhereIn(
                    'sender_type',
                    ['contact', 'ai', 'agent']
                );
            })
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(function (Message $message): array {
                $isVisitor = $message->direction === 'inbound'
                    || $message->sender_type === 'contact'
                    || in_array($message->sender, ['visitor', 'user'], true);

                return [
                    'role' => $isVisitor ? 'user' : 'assistant',
                    'content' => (string) $message->message,
                ];
            })
            ->values()
            ->toArray();
    }

    private function alreadyHasAiReply(
        int $conversationId,
        int $inboundMessageId,
    ): bool {
        $candidateReplies = Message::query()
            ->where('conversation_id', $conversationId)
            ->where('id', '>', $inboundMessageId)
            ->where('is_ai_generated', true)
            ->whereIn('status', ['pending', 'sent', 'delivered', 'read'])
            ->latest('id')
            ->limit(20)
            ->get([
                'id',
                'payload',
                'status',
                'created_at',
            ]);

        foreach ($candidateReplies as $reply) {
            $payload = is_array($reply->payload) ? $reply->payload : [];
            $metadata = is_array($payload['metadata'] ?? null)
                ? $payload['metadata']
                : [];

            if ((int) ($metadata['in_reply_to_message_id'] ?? 0) !== $inboundMessageId) {
                continue;
            }

            if (in_array($reply->status, ['sent', 'delivered', 'read'], true)) {
                return true;
            }

            if (
                $reply->status === 'pending'
                && $reply->created_at
                && $reply->created_at->gt(now()->subMinutes(2))
            ) {
                return true;
            }
        }

        return false;
    }
}
