<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\Omnichannel\OutboundMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOmnichannelMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

<<<<<<< HEAD
    /**
     * We intentionally allow only one automatic attempt for now.
     *
     * OutboundMessageService creates the local Message record before
     * attempting provider delivery. Automatically retrying this whole
     * job could otherwise create duplicate outbound Message rows.
     *
     * We can introduce provider-safe idempotent retries later.
     */
    public int $tries = 1;

    /**
     * Maximum execution time.
     */
    public int $timeout = 90;

    /**
     * Fail the job if it runs beyond the timeout.
     */
=======
    public int $tries = 1;

    public int $timeout = 90;

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public bool $failOnTimeout = true;

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
<<<<<<< HEAD
        /*
         * Keep omnichannel provider calls separate from normal
         * application jobs.
         */
        $this->onQueue('omnichannel');
    }

    /**
     * Execute the queued job.
     */
    public function handle(
        OutboundMessageService $outboundMessageService
=======
        $this->onQueue('omnichannel');
    }

    public function handle(
        OutboundMessageService $outbound
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    ): void {
        $conversation = Conversation::query()
            ->with([
                'channelConnection',
                'contact',
            ])
<<<<<<< HEAD
            ->find($this->conversationId);

        if (!$conversation) {
            Log::warning(
                'Omnichannel outbound job skipped: conversation not found.',
                [
                    'conversation_id' => $this->conversationId,
                ]
            );

            return;
        }

        Log::info(
            'Processing omnichannel outbound message.',
            [
                'conversation_id' => $conversation->id,
                'channel_connection_id' =>
                    $conversation->channel_connection_id,
                'sender_type' => $this->senderType,
                'sender_user_id' => $this->senderUserId,
                'is_ai_generated' => $this->isAiGenerated,
            ]
        );

        $message = $outboundMessageService->send(
=======
            ->findOrFail(
                $this->conversationId
            );

        Log::info(
            'Processing omnichannel outbound message.',
            [
                'conversation_id' =>
                    $conversation->id,

                'channel_connection_id' =>
                    $conversation->channel_connection_id,

                'sender_type' =>
                    $this->senderType,
            ]
        );

        $message = $outbound->send(
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            conversation: $conversation,
            body: $this->body,
            senderType: $this->senderType,
            senderUserId: $this->senderUserId,
            isAiGenerated: $this->isAiGenerated,
            attachments: $this->attachments,
            metadata: $this->metadata,
<<<<<<< HEAD
            replyToExternalId: $this->replyToExternalId,
=======
            replyToExternalId:
                $this->replyToExternalId,
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        );

        Log::info(
            'Omnichannel outbound message processed.',
            [
<<<<<<< HEAD
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'status' => $message->status,
=======
                'conversation_id' =>
                    $conversation->id,

                'message_id' =>
                    $message->id,

                'status' =>
                    $message->status,

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                'external_message_id' =>
                    $message->external_message_id,
            ]
        );
    }

<<<<<<< HEAD
    /**
     * Called when the queued job fails.
     */
    public function failed(Throwable $exception): void
    {
        Log::error(
            'Omnichannel outbound message job failed.',
            [
                'conversation_id' => $this->conversationId,
                'sender_type' => $this->senderType,
                'sender_user_id' => $this->senderUserId,
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]
        );
    }
}
=======
    public function failed(
        ?Throwable $exception
    ): void {
        Log::error(
            'SendOmnichannelMessageJob failed.',
            [
                'conversation_id' =>
                    $this->conversationId,

                'sender_type' =>
                    $this->senderType,

                'error' =>
                    $exception?->getMessage(),
            ]
        );
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
