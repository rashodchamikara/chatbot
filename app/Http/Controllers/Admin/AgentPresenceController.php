<?php

namespace App\Http\Controllers\Admin;

use App\Events\AgentStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\AgentAvailabilityService;
use Illuminate\Http\Request;

class AgentPresenceController extends Controller
{
    public function online(Request $request, AgentAvailabilityService $availability)
    {
        $user = $request->user();

        $user->update([
            'agent_status' => 'online',
            'last_seen_at' => now(),
        ]);

        $this->broadcastAvailabilityForUser($user, $availability);

        return response()->json([
            'status' => 'online',
        ]);
    }

    public function offline(Request $request, AgentAvailabilityService $availability)
    {
        $user = $request->user();

        $user->update([
            'agent_status' => 'offline',
            'last_seen_at' => now(),
        ]);

        $this->broadcastAvailabilityForUser($user, $availability);

        return response()->json([
            'status' => 'offline',
        ]);
    }

    private function broadcastAvailabilityForUser($user, AgentAvailabilityService $availability): void
    {
        $websites = Website::query()
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->where('is_active', true)
            ->get();

        foreach ($websites as $website) {
            broadcast(new AgentStatusChanged(
                $website,
                $availability->hasOnlineAgent($website)
            ));
        }
    }
}