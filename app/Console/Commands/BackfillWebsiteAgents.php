<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\ChannelConnection;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillWebsiteAgents extends Command
{
    protected $signature = 'omnichannel:backfill-website-agents';

    protected $description = 'Create AI agents and website channel connections for existing websites.';

    public function handle(): int
    {
        $processed = 0;
        $skipped = 0;

        Website::query()
            ->orderBy('id')
            ->chunkById(100, function ($websites) use (&$processed, &$skipped): void {
                foreach ($websites as $website) {
                    if (!$website->tenant_id) {
                        $this->warn("Skipping website {$website->id}: tenant_id is missing.");
                        $skipped++;
                        continue;
                    }

                    DB::transaction(function () use ($website): void {
                        $agent = null;

                        if ($website->ai_agent_id) {
                            $agent = AiAgent::query()->find($website->ai_agent_id);
                        }

                        $agentName = trim((string) ($website->bot_name ?? ''));

                        if ($agentName === '') {
                            $agentName = trim((string) $website->name) . ' AI Agent';
                        }

                        if (!$agent) {
                            $agent = new AiAgent();
                            $agent->tenant_id = $website->tenant_id;
                            $agent->name = $agentName;
                            $agent->status = 'active';
                            $agent->instructions = $website->initial_instructions ?? null;
                            $agent->default_language = 'en';
                            $agent->save();
                        }

                        if ((int) $website->ai_agent_id !== (int) $agent->id) {
                            $website->ai_agent_id = $agent->id;
                            $website->save();
                        }

                        $connection = ChannelConnection::query()
                            ->where('website_id', $website->id)
                            ->where('type', 'website')
                            ->first();

                        if (!$connection) {
                            $connection = new ChannelConnection();
                        }

                        // Direct assignments intentionally avoid mass-assignment issues.
                        $connection->tenant_id = $website->tenant_id;
                        $connection->ai_agent_id = $agent->id;
                        $connection->website_id = $website->id;
                        $connection->type = 'website';
                        $connection->provider = 'website';
                        $connection->name = (string) $website->name;
                        $connection->status = 'active';

                        if (!$connection->webhook_key) {
                            $connection->webhook_key = (string) Str::ulid();
                        }

                        $settings = is_array($connection->settings)
                            ? $connection->settings
                            : [];

                        $settings['domain'] = (string) $website->domain;
                        $connection->settings = $settings;

                        if (!$connection->connected_at) {
                            $connection->connected_at = now();
                        }

                        $connection->save();
                    });

                    $processed++;
                    $this->line("Backfilled website {$website->id}: {$website->name}");
                }
            });

        $this->newLine();
        $this->info("Completed. Processed: {$processed}; skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
