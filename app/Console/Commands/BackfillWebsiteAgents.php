<?php

namespace App\Console\Commands;

<<<<<<< HEAD
use App\Models\AiAgent;
use App\Models\ChannelConnection;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
=======
use App\Models\Website;
use App\Services\Omnichannel\WebsiteOmnichannelProvisioner;
use Illuminate\Console\Command;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Throwable;

class BackfillWebsiteAgents extends Command
{
    protected $signature =
<<<<<<< HEAD
        'omnichannel:backfill-website-agents';

    protected $description =
        'Create or repair AI agents and website channel connections for existing websites.';

    public function handle(): int
    {
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        Website::query()
            ->orderBy('id')
            ->chunkById(
                100,
                function ($websites) use (
                    &$processed,
                    &$skipped,
                    &$failed
                ): void {
                    foreach ($websites as $website) {
                        if (!$website->tenant_id) {
                            $this->warn(
                                "Skipping website {$website->id}: tenant_id is missing."
                            );

                            $skipped++;

                            continue;
                        }

                        try {
                            DB::transaction(
                                function () use (
                                    $website
                                ): void {
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Resolve/create AI agent
                                    |--------------------------------------------------------------------------
                                    */

                                    $agent = null;

                                    if ($website->ai_agent_id) {
                                        $agent = AiAgent::query()
                                            ->whereKey(
                                                $website->ai_agent_id
                                            )
                                            ->where(
                                                'tenant_id',
                                                $website->tenant_id
                                            )
                                            ->first();
                                    }

                                    if (!$agent) {
                                        $agent =
                                            new AiAgent();

                                        $agent->tenant_id =
                                            $website->tenant_id;

                                        $agent->name =
                                            trim(
                                                (string)
                                                $website->chatbot_name
                                            )
                                            ?: (
                                                $website->name
                                                . ' AI Agent'
                                            );

                                        $agent->status =
                                            'active';

                                        $agent->instructions =
                                            $website
                                                ->chatbot_instructions;

                                        $agent->default_language =
                                            'en';

                                        $agent->model_settings =
                                            [];

                                        $agent->handover_settings = [
                                            'enabled' =>
                                                (bool)
                                                $website
                                                    ->live_chat_enabled,
                                        ];

                                        $agent->business_hours =
                                            [];

                                        $agent->save();
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Link website → AI Agent
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        (int)
                                        $website->ai_agent_id
                                        !==
                                        (int)
                                        $agent->id
                                    ) {
                                        $website->ai_agent_id =
                                            $agent->id;

                                        $website->save();
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Resolve/create native Website ChannelConnection
                                    |--------------------------------------------------------------------------
                                    */

                                    $connection =
                                        ChannelConnection::query()
                                            ->where(
                                                'website_id',
                                                $website->id
                                            )
                                            ->where(
                                                'type',
                                                'website'
                                            )
                                            ->first();

                                    if (!$connection) {
                                        $connection =
                                            new ChannelConnection();
                                    }

                                    $connection->tenant_id =
                                        $website->tenant_id;

                                    $connection->ai_agent_id =
                                        $agent->id;

                                    $connection->website_id =
                                        $website->id;

                                    $connection->type =
                                        'website';

                                    /*
                                     * "native" means this channel is
                                     * provided by our own website widget,
                                     * not an external vendor.
                                     */
                                    $connection->provider =
                                        'native';

                                    $connection->name =
                                        (string)
                                        $website->name;

                                    $connection->status =
                                        'active';

                                    /*
                                     * The embed token identifies the
                                     * website-side sender/channel.
                                     */
                                    $connection->external_sender_id =
                                        $website->embed_token;

                                    if (!$connection->webhook_key) {
                                        $connection->webhook_key =
                                            (string)
                                            Str::ulid();
                                    }

                                    $settings =
                                        is_array(
                                            $connection->settings
                                        )
                                            ? $connection->settings
                                            : [];

                                    $settings['domain'] =
                                        (string)
                                        $website->domain;

                                    $settings['verify_domain'] =
                                        (bool)
                                        $website->verify_domain;

                                    $settings['live_chat_enabled'] =
                                        (bool)
                                        $website
                                            ->live_chat_enabled;

                                    $connection->settings =
                                        $settings;

                                    if (
                                        !$connection->connected_at
                                    ) {
                                        $connection->connected_at =
                                            now();
                                    }

                                    $connection->last_error =
                                        null;

                                    $connection->save();
                                }
                            );

                            $processed++;

                            $this->line(
                                "Backfilled website {$website->id}: {$website->name}"
                            );
                        } catch (Throwable $exception) {
                            $failed++;

                            $this->error(
                                "Website {$website->id} failed: "
                                . $exception->getMessage()
                            );
                        }
                    }
                }
            );
=======
        'omnichannel:backfill-website-agents
        {--website= : Backfill only one website ID}';

    protected $description =
        'Create or repair AI agents and native channel connections for existing websites.';

    public function handle(
        WebsiteOmnichannelProvisioner $provisioner
    ): int {
        $query = Website::withTrashed()
            ->orderBy('id');

        if ($this->option('website')) {
            $query->where(
                'id',
                (int) $this->option('website')
            );
        }

        $processed = 0;
        $failed = 0;

        $query->chunkById(
            100,
            function ($websites) use (
                $provisioner,
                &$processed,
                &$failed
            ): void {
                foreach ($websites as $website) {
                    try {
                        $connection =
                            $provisioner
                                ->provision($website);

                        $processed++;

                        $this->line(
                            "Website {$website->id}: "
                            . "agent {$connection->ai_agent_id}, "
                            . "channel {$connection->id}"
                        );
                    } catch (Throwable $exception) {
                        $failed++;

                        $this->error(
                            "Website {$website->id} failed: "
                            . $exception->getMessage()
                        );
                    }
                }
            }
        );
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        $this->newLine();

        $this->info(
<<<<<<< HEAD
            "Completed. Processed: {$processed}; "
            . "skipped: {$skipped}; "
            . "failed: {$failed}."
=======
            "Completed. Processed: {$processed}; failed: {$failed}."
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
