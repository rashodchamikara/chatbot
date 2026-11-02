<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
class MessengerPreflight extends Command
{
    protected $signature = 'messenger:preflight';
    protected $description = 'Check the existing database and queue before installing Messenger.';
    public function handle(): int
    {
        $ok = true;
        foreach ([
            'channel_connections' => ['tenant_id', 'ai_agent_id', 'website_id', 'type', 'provider', 'external_sender_id', 'credentials', 'settings'],
            'conversations' => ['tenant_id', 'ai_agent_id', 'channel_connection_id', 'website_id', 'external_thread_id', 'reply_window_expires_at', 'mode'],
            'messages' => ['channel_connection_id', 'external_message_id', 'payload', 'sender_type'],
            'inbound_webhook_events' => ['provider', 'payload_hash', 'payload', 'status'],
        ] as $table => $columns) {
            if (!Schema::hasColumns($table, $columns)) { $this->error("Missing expected schema in {$table}"); $ok = false; }
        }
        if (Schema::hasTable('inbound_webhook_events')) {
            $unique = collect(Schema::getIndexes('inbound_webhook_events'))->contains(fn ($i) => $i['unique']
                && count($i['columns']) === 2 && !array_diff(['provider', 'payload_hash'], $i['columns']));
            if (!$unique) { $this->error('Missing unique inbound_webhook_events(provider, payload_hash) index.'); $ok = false; }
        }
        if (in_array(config('queue.default'), ['sync', 'null'], true)) { $this->error('Configure a persistent queue such as database or redis.'); $ok = false; }
        if ($ok) $this->info('Schema and queue configuration checks passed. Verify workers and Meta configuration separately.');
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
