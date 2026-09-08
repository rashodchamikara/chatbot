<?php

namespace App\Services\Omnichannel;

use App\Enums\ChannelConnectionStatus;
use App\Enums\ChannelType;
use App\Models\AiAgent;
use App\Models\ChannelConnection;
use App\Models\Website;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WebsiteOmnichannelProvisioner
{
    /**
     * Create or synchronize the native website
     * omnichannel infrastructure.
     *
     * Website
     *   -> AiAgent
     *   -> ChannelConnection
     */
    public function provision(
        Website $website
    ): ChannelConnection {
        if (!$website->exists) {
            throw new RuntimeException(
                'Website must be saved before omnichannel provisioning.'
            );
        }

        return DB::transaction(
            function () use (
                $website
            ): ChannelConnection {
                /*
                |--------------------------------------------------------------------------
                | Reload and lock website
                |--------------------------------------------------------------------------
                |
                | Prevent two requests from provisioning the same website
                | simultaneously.
                |
                */

                $website =
                    Website::withTrashed()
                        ->whereKey(
                            $website->getKey()
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$website) {
                    throw new RuntimeException(
                        'Website could not be found during omnichannel provisioning.'
                    );
                }

                if (!$website->tenant_id) {
                    throw new RuntimeException(
                        "Website [{$website->id}] does not have a tenant."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Find existing website channel
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
                            ChannelType::Website->value
                        )
                        ->first();

                /*
                |--------------------------------------------------------------------------
                | Resolve AI Agent
                |--------------------------------------------------------------------------
                */

                $agent = null;

                /*
                 * First preference:
                 * website.ai_agent_id
                 */
                if ($website->ai_agent_id) {
                    $agent =
                        AiAgent::query()
                            ->whereKey(
                                $website->ai_agent_id
                            )
                            ->where(
                                'tenant_id',
                                $website->tenant_id
                            )
                            ->first();
                }

                /*
                 * Second preference:
                 * existing website channel's agent.
                 *
                 * This prevents unnecessary duplicate agents if
                 * website.ai_agent_id was accidentally cleared.
                 */
                if (
                    !$agent
                    && $connection
                    && $connection->ai_agent_id
                ) {
                    $agent =
                        AiAgent::query()
                            ->whereKey(
                                $connection->ai_agent_id
                            )
                            ->where(
                                'tenant_id',
                                $website->tenant_id
                            )
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Create AI Agent when required
                |--------------------------------------------------------------------------
                */

                if (!$agent) {
                    $agent =
                        new AiAgent();

                    $agent->tenant_id =
                        $website->tenant_id;

                    $agent->status =
                        'active';

                    $agent->default_language =
                        'en';

                    $agent->model_settings =
                        [];

                    $agent->handover_settings =
                        [];

                    $agent->business_hours =
                        [];
                }

                /*
                |--------------------------------------------------------------------------
                | Synchronize website-owned AI Agent settings
                |--------------------------------------------------------------------------
                */

                $agent->name =
                    trim(
                        (string)
                        $website->chatbot_name
                    )
                    ?: (
                        $website->name
                        . ' AI Agent'
                    );

                $agent->instructions =
                    $website->chatbot_instructions;

                /*
                 * Preserve future handover configuration.
                 */
                $handoverSettings =
                    is_array(
                        $agent->handover_settings
                    )
                        ? $agent->handover_settings
                        : [];

                $handoverSettings['enabled'] =
                    (bool)
                    $website->live_chat_enabled;

                $agent->handover_settings =
                    $handoverSettings;

                /*
                 * Avoid wiping future model settings.
                 */
                if (
                    !is_array(
                        $agent->model_settings
                    )
                ) {
                    $agent->model_settings =
                        [];
                }

                if (
                    !is_array(
                        $agent->business_hours
                    )
                ) {
                    $agent->business_hours =
                        [];
                }

                $agent->save();

                /*
                |--------------------------------------------------------------------------
                | Link Website -> AI Agent
                |--------------------------------------------------------------------------
                |
                | Saving this website fires WebsiteObserver::updated().
                |
                | The observer deliberately does NOT react to ai_agent_id,
                | preventing recursive provisioning.
                |
                */

                if (
                    (int) $website->ai_agent_id
                    !==
                    (int) $agent->id
                ) {
                    $website->ai_agent_id =
                        $agent->id;

                    $website->save();
                }

                /*
                |--------------------------------------------------------------------------
                | Create ChannelConnection
                |--------------------------------------------------------------------------
                */

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
                    ChannelType::Website->value;

                /*
                 * Native = our own website widget.
                 */
                $connection->provider =
                    'native';

                $connection->name =
                    (string)
                    $website->name;

                /*
                |--------------------------------------------------------------------------
                | Synchronize connection status
                |--------------------------------------------------------------------------
                */

                if ($website->trashed()) {
                    $connection->status =
                        ChannelConnectionStatus::Disconnected
                            ->value;
                } elseif ($website->is_active) {
                    $connection->status =
                        ChannelConnectionStatus::Active
                            ->value;
                } else {
                    $connection->status =
                        ChannelConnectionStatus::Suspended
                            ->value;
                }

                /*
                 * Keep embed token and channel identity synchronized.
                 */
                $connection->external_sender_id =
                    $website->embed_token;

                /*
                 * webhook_key is mandatory and unique.
                 */
                if (!$connection->webhook_key) {
                    $connection->webhook_key =
                        (string)
                        Str::ulid();
                }

                /*
                |--------------------------------------------------------------------------
                | Website channel settings
                |--------------------------------------------------------------------------
                |
                | Preserve unknown/future settings rather than replacing
                | the JSON object completely.
                |
                */

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
                    $website->live_chat_enabled;

                $settings['chatbot_name'] =
                    $website->chatbot_name;

                $settings['chatbot_theme'] =
                    $website->chatbot_theme;

                $settings['chatbot_avatar'] =
                    $website->chatbot_avatar;

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

                return $connection->fresh();
            }
        );
    }

    /**
     * Disconnect website channel when website is deleted.
     */
    public function disconnect(
        Website $website
    ): void {
        ChannelConnection::query()
            ->where(
                'website_id',
                $website->id
            )
            ->where(
                'type',
                ChannelType::Website->value
            )
            ->update([
                'status' =>
                    ChannelConnectionStatus::Disconnected
                        ->value,

                'last_error' =>
                    null,
            ]);
    }
}
