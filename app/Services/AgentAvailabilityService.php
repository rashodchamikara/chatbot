<?php

namespace App\Services;

use App\Events\AgentStatusChanged;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Collection;

class AgentAvailabilityService
{
    public function canProvideLiveChat(User $user): bool
    {
        return $user->tenant_id !== null
            && !$user->isSuperAdmin()
            && in_array($user->role, [User::ROLE_TENANT_ADMIN, User::ROLE_AGENT], true)
            && $user->isActive()
            && !$user->trashed();
    }

    public function onlineAgentsForWebsite(Website $website): Collection
    {
        // A missing tenant must never match unassigned users.
        if (!$website->live_chat_enabled || $website->tenant_id === null) {
            return collect();
        }

        return User::query()
            ->where('tenant_id', $website->tenant_id)
            ->whereIn('role', [User::ROLE_TENANT_ADMIN, User::ROLE_AGENT])
            ->where('status', User::STATUS_ACTIVE)
            ->where('agent_status', 'online')
            ->get();
    }

    public function hasOnlineAgent(Website $website): bool
    {
        return $this->onlineAgentsForWebsite($website)->isNotEmpty();
    }

    public function broadcastAvailabilityForUser(User $user): void
    {
        // Administrative access is not live-agent availability.
        if ($user->isSuperAdmin() || $user->tenant_id === null) {
            return;
        }

        $websites = Website::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        foreach ($websites as $website) {
            broadcast(new AgentStatusChanged($website, $this->hasOnlineAgent($website)));
        }
    }
}
