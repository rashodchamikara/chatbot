<?php
namespace App\Console\Commands;
use App\Jobs\ProcessMessengerWebhookJob;
use App\Models\{InboundWebhookEvent, MessengerOAuthGrant};
use Illuminate\Console\Command;
class MessengerRecover extends Command
{
    protected $signature = 'messenger:recover {--failed : Also enqueue receipts marked failed after the underlying issue is fixed}';
    protected $description = 'Purge expired login grants and recover receipts awaiting queue dispatch.';
    public function handle(): int
    {
        MessengerOAuthGrant::where('expires_at', '<', now())->delete();
        $statuses = $this->option('failed') ? ['pending', 'failed'] : ['pending'];
        InboundWebhookEvent::where('provider', 'meta_messenger')->whereIn('status', $statuses)
            ->where('received_at', '<', now()->subMinutes(5))->orderBy('id')->chunkById(100, function ($events) {
                foreach ($events as $event) ProcessMessengerWebhookJob::dispatch($event->id);
            });
        $this->info('Expired login grants removed; eligible receipts queued.');
        return self::SUCCESS;
    }
}
