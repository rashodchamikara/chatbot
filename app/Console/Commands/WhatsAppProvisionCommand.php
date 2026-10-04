<?php

namespace App\Console\Commands;

use App\Models\ChannelConnection;
use App\Services\Omnichannel\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Console\Command;
use Throwable;

class WhatsAppProvisionCommand extends Command
{
    protected $signature = 'omnichannel:whatsapp-provision
        {connection_id : Saved WhatsApp ChannelConnection ID}
        {--tenant= : Required tenant ID used to verify the selected connection}';

    protected $description = 'Resume Meta Embedded Signup registration, webhook subscription and health checks';

    public function handle(MetaEmbeddedSignupService $service): int
    {
        $id = trim((string) $this->argument('connection_id'));
        $tenant = trim((string) $this->option('tenant'));
        if (!ctype_digit($id) || (int) $id < 1 || !ctype_digit($tenant) || (int) $tenant < 1) {
            $this->error('Supply a positive connection ID and --tenant=TENANT_ID.');
            return self::FAILURE;
        }

        $connection = ChannelConnection::query()->whereKey((int) $id)
            ->where('tenant_id', (int) $tenant)->first();
        if (!$connection) {
            $this->error('Connection not found for the supplied tenant.');
            return self::FAILURE;
        }

        $this->info('Provisioning connection #' . $id . ' for tenant #' . $tenant . '...');
        try {
            $connection = $service->retryProvisioning($connection);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Provisioning could not run. Check the Laravel log.');
            return self::FAILURE;
        }

        if ($connection->status !== 'active') {
            $this->error($connection->last_error ?: 'Provisioning is incomplete.');
            return self::FAILURE;
        }

        $this->info('Webhook subscription and account health checks passed.');
        $this->line('Send a message from another WhatsApp account and verify a ChatNivo reply.');
        return self::SUCCESS;
    }
}
