<?php
namespace App\Services\Omnichannel\Messenger;
/** Pure protocol validation; no database, network, or framework dependencies. */
final class Payload
{
    public static function signatureValid(string $body, ?string $signature, string $secret): bool
    {
        return $secret !== '' && is_string($signature) && preg_match('/^sha256=[a-f0-9]{64}$/', $signature)
            && hash_equals('sha256='.hash_hmac('sha256', $body, $secret), $signature);
    }
    public static function message(array $event, string $pageId, int $now): ?array
    {
        $sender = (string) ($event['sender']['id'] ?? '');
        if (!preg_match('/^[0-9]+$/', $sender) || $sender === $pageId
            || (string) ($event['recipient']['id'] ?? '') !== $pageId || !empty($event['message']['is_echo'])) return null;
        $message = $event['message'] ?? null;
        $postback = $event['postback'] ?? null;
        if (!is_array($message) && !is_array($postback)) return null;
        $timestamp = (int) floor(((float) ($event['timestamp'] ?? 0)) / 1000);
        if ($timestamp <= 0 || $timestamp > $now + 300) return null;
        // A little provider clock skew must never extend the reply window into the future.
        $timestamp = min($timestamp, $now);
        $mid = (string) ($message['mid'] ?? $postback['mid'] ?? '');
        if ($mid === '') {
            if (!$postback) return null;
            $mid = 'postback:'.hash('sha256', json_encode([$pageId, $sender, $event['timestamp'], $postback]));
        }
        $text = $message['text'] ?? $postback['title'] ?? null;
        if ($text !== null && !is_string($text)) return null;
        return ['sender' => $sender, 'mid' => $mid, 'timestamp' => $timestamp, 'text' => $text,
            'attachments' => is_array($message['attachments'] ?? null) ? $message['attachments'] : [],
            'handoff' => ($postback['payload'] ?? '') === 'CHATNIVO_TALK_TO_AGENT'
                || in_array(strtolower(trim($text ?? '')), ['talk to agent', 'talk to an agent', 'human agent'], true)];
    }
    public static function windowOpen(?int $lastInbound, int $now): bool
    {
        return $lastInbound !== null && $lastInbound <= $now && $lastInbound > $now - 86400;
    }
}
