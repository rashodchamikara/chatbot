<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AiAgentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $agents = AiAgent::query()
            ->with('tenant')
            ->withCount([
                'channelConnections',
                'knowledgePages',
                'knowledgeSources',
                'conversations',
            ])
            ->when(
                !$user->isSuperAdmin(),
                fn ($query) => $query->where('tenant_id', $user->tenant_id)
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.ai-agents.index', compact('agents'));
    }

    public function edit(Request $request, AiAgent $aiAgent)
    {
        $this->authorizeAgentAccess($request, $aiAgent);
        $aiAgent->load('tenant');

        return view('admin.ai-agents.edit', compact('aiAgent'));
    }

    public function update(
        Request $request,
        AiAgent $aiAgent
    ): RedirectResponse {
        $this->authorizeAgentAccess($request, $aiAgent);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'default_language' => ['required', 'string', 'max:20'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'handover_enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $aiAgent, $validated): void {
            $makeDefault = $request->boolean('is_default');

            if ($makeDefault) {
                AiAgent::query()
                    ->where('tenant_id', $aiAgent->tenant_id)
                    ->where('id', '<>', $aiAgent->id)
                    ->update(['is_default' => false]);
            }

            $handoverSettings = is_array($aiAgent->handover_settings)
                ? $aiAgent->handover_settings
                : [];

            $handoverSettings['enabled'] = $request->boolean('handover_enabled');

            $aiAgent->forceFill([
                'name' => trim($validated['name']),
                'status' => $validated['status'],
                'default_language' => trim($validated['default_language']),
                'instructions' => $validated['instructions'] ?? null,
                'handover_settings' => $handoverSettings,
                'is_default' => $makeDefault ?: $aiAgent->is_default,
            ])->save();
        });

        return redirect()
            ->route('admin.ai-agents.index')
            ->with('success', 'AI agent settings updated.');
    }

    private function authorizeAgentAccess(Request $request, AiAgent $agent): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $agent->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Unauthorized AI agent access.');
        }
    }
}
