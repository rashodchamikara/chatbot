<?php

namespace App\Observers;

use App\Models\Website;
use App\Services\Omnichannel\WebsiteOmnichannelProvisioner;

class WebsiteObserver
{
    public function __construct(
        protected WebsiteOmnichannelProvisioner $provisioner
    ) {
    }

    /**
     * Automatically provision every newly created website.
     */
    public function created(
        Website $website
    ): void {
        $this->provisioner
            ->provision(
                $website
            );
    }

    /**
     * Keep website channel configuration synchronized.
     */
    public function updated(
        Website $website
    ): void {
        /*
         * IMPORTANT:
         *
         * Do not include ai_agent_id here.
         *
         * The provisioner itself sets ai_agent_id and saves
         * the website. Including it would cause recursive
         * observer execution.
         */

        $relevantFields = [
            'tenant_id',

            'name',
            'domain',

            'verify_domain',
            'is_active',

            'embed_token',

            'chatbot_name',
            'chatbot_theme',
            'chatbot_avatar',
            'chatbot_instructions',
            'live_chat_enabled',
        ];

        if (
            !$website->wasChanged(
                $relevantFields
            )
        ) {
            return;
        }

        $this->provisioner
            ->provision(
                $website
            );
    }

    /**
     * Soft-deleted websites must no longer have
     * an active channel.
     */
    public function deleted(
        Website $website
    ): void {
        $this->provisioner
            ->disconnect(
                $website
            );
    }

    /**
     * Restore the native channel automatically
     * when a soft-deleted website is restored.
     */
    public function restored(
        Website $website
    ): void {
        $this->provisioner
            ->provision(
                $website
            );
    }
}
