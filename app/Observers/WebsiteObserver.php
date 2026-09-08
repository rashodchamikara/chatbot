<?php

namespace App\Observers;

use App\Models\Website;
use App\Services\Omnichannel\WebsiteOmnichannelProvisioner;
<<<<<<< HEAD
=======
use Illuminate\Support\Facades\Log;
use Throwable;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

class WebsiteObserver
{
    public function __construct(
        protected WebsiteOmnichannelProvisioner $provisioner
    ) {
    }

<<<<<<< HEAD
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

=======
    public function created(
        Website $website
    ): void {
        $this->safeProvision($website);
    }

    public function updated(
        Website $website
    ): void {
        $relevantFields = [
            'tenant_id',
            'name',
            'domain',
            'verify_domain',
            'is_active',
            'embed_token',
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            'chatbot_name',
            'chatbot_theme',
            'chatbot_avatar',
            'chatbot_instructions',
<<<<<<< HEAD

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            'live_chat_enabled',
        ];

        if (
            !$website->wasChanged(
                $relevantFields
            )
        ) {
            return;
        }

<<<<<<< HEAD
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
=======
        $this->safeProvision($website);
    }

    public function deleted(
        Website $website
    ): void {
        try {
            $this->provisioner
                ->disconnect($website);
        } catch (Throwable $exception) {
            Log::error(
                'Failed to disconnect website omnichannel connection.',
                [
                    'website_id' =>
                        $website->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public function restored(
        Website $website
    ): void {
        $this->safeProvision($website);
    }

    private function safeProvision(
        Website $website
    ): void {
        try {
            $this->provisioner
                ->provision($website);
        } catch (Throwable $exception) {
            /*
             * Do not make normal website create/update fail solely
             * because omnichannel provisioning failed. The failure is
             * visible in logs and can be repaired by the backfill command.
             */
            Log::error(
                'Website omnichannel provisioning failed.',
                [
                    'website_id' =>
                        $website->id,

                    'tenant_id' =>
                        $website->tenant_id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
