<?php
namespace App\Console\Commands;

use App\Enums\ChannelConnectionStatus;
use App\Enums\ChannelType;
use App\Models\AiAgent;
use App\Models\ChannelConnection;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillWebsiteAiAgents extends Command
{
    protected $signature = 'omnichannel:backfill-website-agents';

    protected $description =
        'Creates AI agents and website channel connections for existing websites';

    public function handle(): int
    {
        Website::query()
            ->whereNull('ai_agent_id')
            ->chunkById(100, function ($websites): void {
                foreach ($websites as $website) {
                    DB::transaction(function () use ($website): void {
                        $agent = AiAgent::create([
                            'tenant_id' => $website->tenant_id,
                            'name' => $website->bot_name
                                ?: "{$website->name} AI Agent",
                            'instructions' =>
                                $website->initial_instructions ?? null,
                            'default_language' => 'en',
                            'model_settings' => [],
                            'handover_settings' => [
                                'enabled' => true,
                            ],
                        ]);

                        $website->update([
                            'ai_agent_id' => $agent->id,
                        ]);

                        ChannelConnection::firstOrCreate(
                            [
                                'type' => ChannelType::Website->value,
                                'provider' => 'native',
                                'external_sender_id' =>
                                    $website->embed_token,
                            ],
                            [
                                'tenant_id' => $website->tenant_id,
                                'ai_agent_id' => $agent->id,
                                'website_id' => $website->id,
                                'name' => $website->name,
                                'status' =>
                                    ChannelConnectionStatus::Active->value,
                                'connected_at' => now(),
                                'settings' => [
                                    'domain' => $website->domain,
                                ],
                            ]
                        );
                    });
                }
            });

        $this->info('Website AI-agent backfill completed.');

        return self::SUCCESS;
    }
}