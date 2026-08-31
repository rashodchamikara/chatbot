<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\Omnichannel\OutboundMessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOmnichannelMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

   
    public int $tries = 1;

    
    public int $timeout = 60;

    public function __construct(
        public int $conversationId,
        public string $body,
        public string $senderType = 'agent',
        public ?int $senderUserId = null,
        public bool $isAiGenerated = false,
        public array $attachments = [],
        public array $metadata = [],
        public ?string $replyToExternalId = null,
    ) {
    }

    public function handle(
        OutboundMessageService $outboundMessageService
    ): void {
       

        $conversation = Conversation::query()
            ->find($this->conversationId);

        
        if (!$conversation) {
            Log::warning(
                'Omnichannel outbound job skipped because conversation no longer exists.',
                [
                    'conversation_id' =>
                        $this->conversationId,
                ]
            );

            return;
        }

        

        $outboundMessageService->send(
            conversation:
                $conversation,

            body:
                $this->body,

            senderType:
                $this->senderType,

            senderUserId:
                $this->senderUserId,

            isAiGenerated:
                $this->isAiGenerated,

            attachments:
                $this->attachments,

            metadata:
                $this->metadata,

            replyToExternalId:
                $this->replyToExternalId,
        );
    }

    public function failed(
        ?Throwable $exception
    ): void {
        Log::error(
            'Omnichannel outbound message job failed.',
            [
                'conversation_id' =>
                    $this->conversationId,

                'sender_type' =>
                    $this->senderType,

                'sender_user_id' =>
                    $this->senderUserId,

                'is_ai_generated' =>
                    $this->isAiGenerated,

                'error' =>
                    $exception?->getMessage(),
            ]
        );
    }
}