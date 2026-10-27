<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AgentAvailabilityService;
use Illuminate\Http\Request;

class AgentPresenceController extends Controller
{
    public function online(Request $request, AgentAvailabilityService $availability)
    {
        $user = $request->user();
        abort_unless($user, 401);

        // The shared layout calls this endpoint for every signed-in role.
        // A super admin receives a successful no-op rather than becoming an agent.
        $eligible = $availability->canProvideLiveChat($user);
        $user->forceFill([
            'agent_status' => $eligible ? 'online' : 'offline',
            'last_seen_at' => now(),
        ])->save();

        $availability->broadcastAvailabilityForUser($user);

        return response()->json([
            'status' => $eligible ? 'online' : 'offline',
            'last_seen_at' => $user->last_seen_at?->toDateTimeString(),
            'live_agent_eligible' => $eligible,
        ]);
    }

    public function offline(Request $request, AgentAvailabilityService $availability)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $user->forceFill([
            'agent_status' => 'offline',
            'last_seen_at' => now(),
        ])->save();

        $availability->broadcastAvailabilityForUser($user);

        return response()->json([
            'status' => 'offline',
        ]);
    }
}
