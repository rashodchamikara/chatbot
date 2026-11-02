<?php
namespace App\Services\Omnichannel\Adapters;
use App\Contracts\Omnichannel\ChannelAdapter;
use App\Data\Omnichannel\{InboundMessageData, OutboundMessageData, SendResult};
use App\Models\{ChannelConnection, Conversation};
use App\Services\Omnichannel\Messenger\{GraphClient, Payload};
use Illuminate\Http\Request;
use Carbon\Carbon;
use RuntimeException;

class MessengerAdapter implements ChannelAdapter
{
    public function __construct(private readonly GraphClient $graph) {}
    public function type(): string { return 'facebook'; }
    public function parseInbound(ChannelConnection $connection, Request $request): ?InboundMessageData
    {
        foreach ($request->input('entry', []) as $entry) {
            if ((string) ($entry['id'] ?? '') !== (string) $connection->external_sender_id) continue;
            foreach ($entry['messaging'] ?? [] as $event) {
                if (is_array($event) && ($dto = $this->parseEvent($connection, $event))) return $dto;
            }
        }
        return null;
    }
    public function parseEvent(ChannelConnection $connection, array $event): ?InboundMessageData
    {
        $message = Payload::message($event, (string) $connection->external_sender_id, time());
        if (!$message) return null;
        $attachments = [];
        foreach ($message['attachments'] as $attachment) {
            $url = $attachment['payload']['url'] ?? null;
            // Store only HTTPS metadata. Never fetch arbitrary webhook URLs on the server.
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') continue;
            $attachments[] = ['type' => in_array($attachment['type'] ?? '', ['image', 'audio', 'video', 'file'], true) ? $attachment['type'] : 'file',
                'external_url' => $url, 'original_name' => 'Messenger attachment'];
        }
        return new InboundMessageData(tenantId: (int) $connection->tenant_id, channelConnectionId: (int) $connection->id,
            externalContactId: $message['sender'], externalMessageId: $message['mid'], externalThreadId: $message['sender'],
            messageType: $message['text'] ? 'text' : 'file', text: $message['text'] ?? '[Customer sent an attachment]', attachments: $attachments,
            metadata: ['provider_timestamp' => $message['timestamp'], 'request_human' => $message['handoff'],
                'attachment_count' => count($message['attachments'])]);
    }
    public function send(ChannelConnection $connection, OutboundMessageData $message): SendResult
    {
        $connection->refresh();
        $conversation = Conversation::whereKey($message->conversationId)->where('tenant_id', $message->tenantId)
            ->where('channel_connection_id', $connection->id)->first();
        if (!config('messenger.enabled') || !$conversation || $connection->status !== 'active'
            || (int) $connection->id !== $message->channelConnectionId || (int) $connection->tenant_id !== $message->tenantId || $connection->tenant?->status !== 'active')
            return SendResult::failure('This Messenger connection is unavailable.', 'connection_unavailable');
        if ((string) $conversation->external_thread_id !== $message->externalContactId)
            return SendResult::failure('Messenger recipient does not match the conversation.', 'recipient_mismatch');
        if (($message->metadata['source'] ?? '') === 'omnichannel_ai' && $conversation->mode !== 'ai')
            return SendResult::failure('A live agent is handling this conversation.', 'human_handoff');
        $last = $conversation->messenger_last_user_message_at;
        if (!Payload::windowOpen($last ? Carbon::parse($last)->timestamp : null, time()))
            return SendResult::failure('Messenger’s 24-hour reply window has closed. The customer must message your Page again.', 'messenger_window_closed');
        if ($message->attachments || trim($message->text ?? '') === '' || mb_strlen($message->text ?? '') > 2000)
            return SendResult::failure('Messenger currently supports text replies of 1–2,000 characters.', 'unsupported_message');
        $token = data_get($connection->credentials, 'page_access_token');
        if (!$token) return SendResult::failure('Reconnect this Facebook Page.', 'missing_token');
        try {
            $result = $this->graph->request('POST', $connection->external_sender_id.'/messages', $token, [
                'recipient' => json_encode(['id' => $message->externalContactId]), 'messaging_type' => 'RESPONSE',
                'message' => json_encode(['text' => $message->text]),
            ]);
            if (empty($result['message_id'])) return SendResult::failure('Meta did not confirm delivery. Check the Page inbox before retrying.', 'unconfirmed_send');
            return SendResult::success((string) $result['message_id']);
        } catch (RuntimeException $e) {
            $connection->update(['last_error' => $e->getMessage()]);
            return SendResult::failure($e->getMessage(), (string) $e->getCode());
        }
    }
}
