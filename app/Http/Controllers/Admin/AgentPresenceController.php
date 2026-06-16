<?php

namespace App\Http\Controllers\Admin;

use App\Events\AgentStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\AgentAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentPresenceController extends Controller
{
    public function online(Request $request, AgentAvailabilityService $availability)
    {
        $user = $request->user();

        Log::warning('AGENT ONLINE endpoint called', [
            'user_id' => $user->id,
            'email' => $user->email,
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'referer' => $request->header('referer'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'time' => now()->toDateTimeString(),
        ]);
        $user->forceFill([
            'agent_status' => 'online',
            'last_seen_at' => now(),
        ])->save();

        $this->broadcastAvailabilityForUser($user, $availability);

        return response()->json([
            'status' => $user->fresh()->agent_status,
            'last_seen_at' => $user->fresh()
                ->last_seen_at
                ?->toDateTimeString(),
        ]);
    }

    public function offline(Request $request, AgentAvailabilityService $availability)
    {
        $user = $request->user();

        Log::error('AGENT OFFLINE endpoint called', [
            'user_id' => $user->id,
            'email' => $user->email,
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'referer' => $request->header('referer'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'time' => now()->toDateTimeString(),
        ]);
        $user->forceFill([
            'agent_status' => 'offline',
            'last_seen_at' => now(),
        ])->save();

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