<?php
namespace App\Jobs;
use App\Events\OmnichannelMessageChanged;
use App\Models\{ChannelConnection, InboundWebhookEvent, Message};
use App\Services\Omnichannel\Adapters\MessengerAdapter;
use App\Services\Omnichannel\InboundMessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;

class ProcessMessengerWebhookJob implements ShouldQueue
{
    use Queueable;
    public int $tries = 5;
    public int $timeout = 90;
    public function __construct(public int $webhookEventId) { $this->onQueue('omnichannel'); }
    public function backoff(): array { return [10, 30, 120, 300]; }
    public function handle(MessengerAdapter $adapter, InboundMessageService $inbound): void
    {
        DB::transaction(function () use ($adapter, $inbound) {
            $receipt = InboundWebhookEvent::whereKey($this->webhookEventId)->lockForUpdate()->first();
            if (!$receipt || $receipt->status === 'processed') return;
            $receipt->update(['status' => 'processing', 'attempts' => $receipt->attempts + 1, 'processing_started_at' => now()]);
            $payload = json_decode($receipt->payload, true);
            foreach ($payload['entry'] ?? [] as $entry) {
                if (!is_array($entry) || !preg_match('/^[0-9]+$/', (string) ($entry['id'] ?? ''))) continue;
                $claim = DB::table('messenger_page_claims')->where('page_id', (string) $entry['id'])->first();
                if (!$claim) continue;
                // Serialize per Page: prevents duplicate mids across differently batched webhook payloads.
                $connection = ChannelConnection::whereKey($claim->channel_connection_id)->where('tenant_id', $claim->tenant_id)
                    ->where('type', 'facebook')->where('provider', 'meta')->where('external_sender_id', (string) $entry['id'])->lockForUpdate()->first();
                if (!config('messenger.enabled') || !$connection || $connection->status !== 'active' || $connection->tenant?->status !== 'active') continue;
                foreach ($entry['messaging'] ?? [] as $event) {
                    if (!is_array($event) || !($data = $adapter->parseEvent($connection, $event))) continue;
                    if (Message::where('channel_connection_id', $connection->id)->where('external_message_id', $data->externalMessageId)->exists()) continue;
                    $message = $inbound->handle($data);
                    $conversation = $message->conversation()->lockForUpdate()->firstOrFail();
                    $time = Carbon::createFromTimestampUTC($data->metadata['provider_timestamp']);
                    if (!$conversation->messenger_last_user_message_at || $time->gt(Carbon::parse($conversation->messenger_last_user_message_at))) {
                        $conversation->messenger_last_user_message_at = $time;
                        $conversation->reply_window_expires_at = $time->copy()->addHours(24);
                    }
                    $needsHuman = $data->metadata['request_human'] || ($data->messageType !== 'text') || !trim($data->text ?? '');
                    if ($needsHuman && $conversation->mode !== 'live') {
                        $conversation->mode = 'live_waiting';
                        $conversation->live_requested_at ??= now();
                        $conversation->assigned_agent_id = null;
                        $conversation->assigned_user_id = null;
                    }
                    $conversation->save();
                    DB::afterCommit(function () use ($message) {
                        try { OmnichannelMessageChanged::dispatch($message->fresh(['conversation']), 'created'); } catch (Throwable $e) { /* Polling remains available. */ }
                    });
                    if (!$needsHuman && $conversation->mode === 'ai' && $time->gt(now()->subHours(24))) {
                        GenerateOmnichannelAiReplyJob::dispatch($message->id)->afterCommit();
                    }
                }
                $connection->update(['last_webhook_at' => now()]);
            }
            $receipt->update(['status' => 'processed', 'processed_at' => now(), 'failed_at' => null, 'last_error' => null]);
        }, 3);
    }
    public function failed(?Throwable $e): void
    {
        InboundWebhookEvent::whereKey($this->webhookEventId)->where('status', '!=', 'processed')
            ->update(['status' => 'failed', 'failed_at' => now(), 'last_error' => 'Messenger processing failed. Inspect the failed queue job and retry after resolving the cause.']);
    }
}
