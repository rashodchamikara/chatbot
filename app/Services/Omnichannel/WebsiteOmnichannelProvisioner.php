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
<<<<<<< HEAD
    /**
     * Create or synchronize the native website
     * omnichannel infrastructure.
     *
     * Website
     *   -> AiAgent
     *   -> ChannelConnection
     */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function provision(
        Website $website
    ): ChannelConnection {
        if (!$website->exists) {
            throw new RuntimeException(
                'Website must be saved before omnichannel provisioning.'
            );
        }

        return DB::transaction(
<<<<<<< HEAD
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
=======
            function () use ($website): ChannelConnection {
                $website = Website::withTrashed()
                    ->whereKey($website->getKey())
                    ->lockForUpdate()
                    ->first();
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

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

<<<<<<< HEAD
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
=======
                $connection = ChannelConnection::query()
                    ->where('website_id', $website->id)
                    ->where('type', ChannelType::Website->value)
                    ->first();

                $agent = null;

                if ($website->ai_agent_id) {
                    $agent = AiAgent::query()
                        ->whereKey($website->ai_agent_id)
                        ->where('tenant_id', $website->tenant_id)
                        ->first();
                }

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                if (
                    !$agent
                    && $connection
                    && $connection->ai_agent_id
                ) {
<<<<<<< HEAD
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
=======
                    $agent = AiAgent::query()
                        ->whereKey($connection->ai_agent_id)
                        ->where('tenant_id', $website->tenant_id)
                        ->first();
                }

                if (!$agent) {
                    $agent = new AiAgent();
                    $agent->tenant_id = $website->tenant_id;
                    $agent->status = 'active';
                    $agent->default_language = 'en';
                    $agent->model_settings = [];
                    $agent->handover_settings = [];
                    $agent->business_hours = [];
                }

                $agent->name =
                    trim((string) $website->chatbot_name)
                    ?: ($website->name . ' AI Agent');
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

                $agent->instructions =
                    $website->chatbot_instructions;

<<<<<<< HEAD
                /*
                 * Preserve future handover configuration.
                 */
                $handoverSettings =
                    is_array(
                        $agent->handover_settings
                    )
=======
                $handoverSettings =
                    is_array($agent->handover_settings)
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                        ? $agent->handover_settings
                        : [];

                $handoverSettings['enabled'] =
<<<<<<< HEAD
                    (bool)
                    $website->live_chat_enabled;
=======
                    (bool) $website->live_chat_enabled;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

                $agent->handover_settings =
                    $handoverSettings;

<<<<<<< HEAD
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
=======
                if (!is_array($agent->model_settings)) {
                    $agent->model_settings = [];
                }

                if (!is_array($agent->business_hours)) {
                    $agent->business_hours = [];
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                }

                $agent->save();

<<<<<<< HEAD
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
=======
                if (
                    (int) $website->ai_agent_id
                    !== (int) $agent->id
                ) {
                    /*
                     * saveQuietly prevents the observer from re-entering
                     * provisioning only because ai_agent_id changed.
                     */
                    $website->ai_agent_id = $agent->id;
                    $website->saveQuietly();
                }

                if (!$connection) {
                    $connection = new ChannelConnection();
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                }

                $connection->tenant_id =
                    $website->tenant_id;

                $connection->ai_agent_id =
                    $agent->id;

                $connection->website_id =
                    $website->id;

                $connection->type =
                    ChannelType::Website->value;

<<<<<<< HEAD
                /*
                 * Native = our own website widget.
                 */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                $connection->provider =
                    'native';

                $connection->name =
<<<<<<< HEAD
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
=======
                    (string) $website->name;

                if ($website->trashed()) {
                    $connection->status =
                        ChannelConnectionStatus::Disconnected->value;
                } elseif ($website->is_active) {
                    $connection->status =
                        ChannelConnectionStatus::Active->value;
                } else {
                    $connection->status =
                        ChannelConnectionStatus::Suspended->value;
                }

                $connection->external_sender_id =
                    $website->embed_token;

                if (!$connection->webhook_key) {
                    $connection->webhook_key =
                        (string) Str::ulid();
                }

                $settings =
                    is_array($connection->settings)
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                        ? $connection->settings
                        : [];

                $settings['domain'] =
<<<<<<< HEAD
                    (string)
                    $website->domain;

                $settings['verify_domain'] =
                    (bool)
                    $website->verify_domain;

                $settings['live_chat_enabled'] =
                    (bool)
                    $website->live_chat_enabled;
=======
                    (string) $website->domain;

                $settings['verify_domain'] =
                    (bool) $website->verify_domain;

                $settings['live_chat_enabled'] =
                    (bool) $website->live_chat_enabled;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

                $settings['chatbot_name'] =
                    $website->chatbot_name;

                $settings['chatbot_theme'] =
                    $website->chatbot_theme;

                $settings['chatbot_avatar'] =
                    $website->chatbot_avatar;

                $connection->settings =
                    $settings;

<<<<<<< HEAD
                if (
                    !$connection->connected_at
                ) {
                    $connection->connected_at =
                        now();
                }

                $connection->last_error =
                    null;

=======
                if (!$connection->connected_at) {
                    $connection->connected_at = now();
                }

                $connection->last_error = null;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                $connection->save();

                return $connection->fresh();
            }
        );
    }

<<<<<<< HEAD
    /**
     * Disconnect website channel when website is deleted.
     */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function disconnect(
        Website $website
    ): void {
        ChannelConnection::query()
<<<<<<< HEAD
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
=======
            ->where('website_id', $website->id)
            ->where('type', ChannelType::Website->value)
            ->update([
                'status' =>
                    ChannelConnectionStatus::Disconnected->value,
                'last_error' => null,
            ]);
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
