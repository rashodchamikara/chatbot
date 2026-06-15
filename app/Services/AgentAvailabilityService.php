<?php

namespace App\Services;

use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Collection;

class AgentAvailabilityService
{
    public function onlineAgentsForWebsite(Website $website): Collection
    {
        if (!$website->live_chat_enabled) {
            return collect();
        }

        return User::query()
            ->where('agent_status', 'online')
            ->where(function ($query) use ($website) {
                $query->where('tenant_id', $website->tenant_id)
                    ->orWhere('role', 'super_admin');
            })
            ->get();
    }

    public function hasOnlineAgent(Website $website): bool
    {
        return $this->onlineAgentsForWebsite($website)->isNotEmpty();
    }
}