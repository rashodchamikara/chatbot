<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeSourceRequest;
use App\Jobs\Knowledge\ExtractKnowledgeSourceJob;
use App\Models\AiAgent;
use App\Models\KnowledgePage;
use App\Models\KnowledgeSource;
use App\Services\Knowledge\ManualKnowledgeIndexer;
use App\Services\Omnichannel\AiAgentProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class KnowledgeHubController extends Controller
{
    public function index(
        Request $request,
        AiAgentProvisioningService $provisioningService,
    ) {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->tenant_id) {
            abort(403, 'This account is not assigned to a tenant.');
        }

        if (!$user->isSuperAdmin()) {
            $provisioningService->ensureDefaultForTenant((int) $user->tenant_id);
        }

        $agentsQuery = AiAgent::query()
            ->with('tenant')
            ->orderByDesc('is_default')
            ->orderBy('name');

        if (!$user->isSuperAdmin()) {
            $agentsQuery->where('tenant_id', $user->tenant_id);
        } elseif ($request->filled('tenant_id')) {
            $agentsQuery->where('tenant_id', (int) $request->input('tenant_id'));
        }

        $agents = $agentsQuery->get();

        $selectedAgent = null;

        if ($request->filled('agent_id')) {
            $selectedAgent = $agents->firstWhere('id', (int) $request->input('agent_id'));
        }

        $selectedAgent ??= $agents->firstWhere('is_default', true)
            ?: $agents->first();

        $pages = collect();
        $sources = collect();
        $stats = [
            'manual' => 0,
            'files' => 0,
            'chunks' => 0,
            'ready' => 0,
        ];

        if ($selectedAgent) {
            $pages = KnowledgePage::query()
                ->where('ai_agent_id', $selectedAgent->id)
                ->withCount('chunks')
                ->latest()
                ->get();

            $sources = KnowledgeSource::query()
                ->where('ai_agent_id', $selectedAgent->id)
                ->latest()
                ->get();

            $stats = [
                'manual' => $pages->where('source_type', 'manual')->count(),
                'files' => $sources->count(),
                'chunks' => $pages->sum('chunks_count') + $sources->sum('chunk_count'),
                'ready' => $pages->where('is_indexed', true)->where('is_active', true)->count()
                    + $sources->where('status', 'ready')->where('is_enabled', true)->count(),
            ];
        }

        return view('admin.knowledge-hub.index', compact(
            'agents',
            'selectedAgent',
            'pages',
            'sources',
            'stats'
        ));
    }

    public function create(Request $request)
    {
        $agents = $this->accessibleAgents($request);
        $selectedAgentId = (int) ($request->input('agent_id') ?: $agents->first()?->id);

        return view('admin.knowledge-hub.create', compact(
            'agents',
            'selectedAgentId'
        ));
    }

    public function store(
        Request $request,
        ManualKnowledgeIndexer $indexer,
    ): RedirectResponse {
        $validated = $request->validate([
            'ai_agent_id' => ['required', 'integer', 'exists:ai_agents,id'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:1000'],
            'type' => [
                'required',
                Rule::in([
                    'page',
                    'product',
                    'blog',
                    'whitepaper',
                    'faq',
                    'service',
                    'pricing',
                    'policy',
                    'business_info',
                    'other',
                ]),
            ],
            'content' => ['required', 'string', 'min:50', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $agent = AiAgent::query()->findOrFail((int) $validated['ai_agent_id']);
        $this->authorizeAgentAccess($request, $agent);

        $page = KnowledgePage::query()->create([
            'tenant_id' => $agent->tenant_id,
            'ai_agent_id' => $agent->id,
            'website_id' => null,
            'url' => $validated['url']
                ?: 'manual://' . Str::slug($validated['title']) . '-' . Str::lower(Str::random(8)),
            'title' => $validated['title'],
            'type' => $validated['type'],
            'source_type' => 'manual',
            'content' => $validated['content'],
            'content_hash' => hash('sha256', $validated['content']),
            'is_indexed' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        try {
            $chunkCount = $indexer->index($page);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.knowledge-hub.index', ['agent_id' => $agent->id])
                ->with('warning',
                    'Knowledge was saved, but indexing failed. You can retry indexing from the Knowledge Hub. Error: '
                    . $exception->getMessage()
                );
        }

        return redirect()
            ->route('admin.knowledge-hub.index', ['agent_id' => $agent->id])
            ->with('success',
                "Knowledge added and trained successfully ({$chunkCount} searchable chunks)."
            );
    }

    public function storeFiles(
        StoreKnowledgeSourceRequest $request,
    ): RedirectResponse {
        $request->validate([
            'ai_agent_id' => ['required', 'integer', 'exists:ai_agents,id'],
        ]);

        $agent = AiAgent::query()->findOrFail((int) $request->input('ai_agent_id'));
        $this->authorizeAgentAccess($request, $agent);

        $disk = config('knowledge.disk');
        $queued = 0;
        $duplicates = 0;

        foreach ($request->file('files') as $file) {
            $checksum = hash_file('sha256', $file->getRealPath());

            $alreadyExists = KnowledgeSource::query()
                ->where('tenant_id', $agent->tenant_id)
                ->where('ai_agent_id', $agent->id)
                ->where('checksum_sha256', $checksum)
                ->whereNull('deleted_at')
                ->exists();

            if ($alreadyExists) {
                $duplicates++;
                continue;
            }

            $sourceUuid = (string) Str::uuid();
            $extension = strtolower($file->getClientOriginalExtension());

            $directory = sprintf(
                'knowledge/tenants/%d/agents/%d/sources/%s',
                $agent->tenant_id,
                $agent->id,
                $sourceUuid
            );

            $storagePath = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                'original.' . $extension,
                ['visibility' => 'private']
            );

            try {
                $source = KnowledgeSource::query()->create([
                    'uuid' => $sourceUuid,
                    'tenant_id' => $agent->tenant_id,
                    'ai_agent_id' => $agent->id,
                    'website_id' => null,
                    'uploaded_by' => $request->user()->id,
                    'source_type' => $this->resolveSourceType($extension),
                    'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'original_name' => $file->getClientOriginalName(),
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath,
                    'mime_type' => $file->getMimeType(),
                    'extension' => $extension,
                    'size_bytes' => $file->getSize(),
                    'checksum_sha256' => $checksum,
                    'status' => 'queued',
                    'is_enabled' => true,
                    'processing_version' => 1,
                    'active_version' => 0,
                ]);
            } catch (Throwable $exception) {
                Storage::disk($disk)->delete($storagePath);
                throw $exception;
            }

            ExtractKnowledgeSourceJob::dispatch($source->id)
                ->onQueue('knowledge-extract')
                ->afterCommit();

            $queued++;
        }

        $message = "{$queued} document(s) queued for AI training.";

        if ($duplicates > 0) {
            $message .= " {$duplicates} duplicate file(s) skipped.";
        }

        return redirect()
            ->route('admin.knowledge-hub.index', ['agent_id' => $agent->id])
            ->with('success', $message);
    }

    public function indexPage(
        Request $request,
        KnowledgePage $knowledgePage,
        ManualKnowledgeIndexer $indexer,
    ): RedirectResponse {
        $this->authorizeKnowledgePageAccess($request, $knowledgePage);

        try {
            $chunkCount = $indexer->index($knowledgePage);
        } catch (Throwable $exception) {
            return back()->with('warning', 'Indexing failed: ' . $exception->getMessage());
        }

        return back()->with('success', "Knowledge re-indexed successfully ({$chunkCount} chunks).");
    }

    public function togglePage(
        Request $request,
        KnowledgePage $knowledgePage,
    ): RedirectResponse {
        $this->authorizeKnowledgePageAccess($request, $knowledgePage);

        $knowledgePage->is_active = !$knowledgePage->is_active;
        $knowledgePage->save();

        $knowledgePage->chunks()->update([
            'is_active' => $knowledgePage->is_active && $knowledgePage->is_indexed,
        ]);

        return back()->with('success', 'Knowledge status updated.');
    }

    public function destroyPage(
        Request $request,
        KnowledgePage $knowledgePage,
    ): RedirectResponse {
        $this->authorizeKnowledgePageAccess($request, $knowledgePage);
        $knowledgePage->chunks()->delete();
        $knowledgePage->delete();

        return back()->with('success', 'Manual knowledge deleted.');
    }

    public function destroySource(
        Request $request,
        KnowledgeSource $knowledgeSource,
    ): RedirectResponse {
        $this->authorizeSourceAccess($request, $knowledgeSource);

        $knowledgeSource->chunks()->delete();

        if ($knowledgeSource->storage_disk && $knowledgeSource->storage_path) {
            Storage::disk($knowledgeSource->storage_disk)
                ->delete($knowledgeSource->storage_path);
        }

        if ($knowledgeSource->storage_disk && $knowledgeSource->extracted_storage_path) {
            Storage::disk($knowledgeSource->storage_disk)
                ->delete($knowledgeSource->extracted_storage_path);
        }

        $knowledgeSource->delete();

        return back()->with('success', 'Knowledge document deleted.');
    }

    private function accessibleAgents(Request $request)
    {
        $user = $request->user();

        return AiAgent::query()
            ->with('tenant')
            ->when(
                !$user->isSuperAdmin(),
                fn ($query) => $query->where('tenant_id', $user->tenant_id)
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
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

    private function authorizeKnowledgePageAccess(
        Request $request,
        KnowledgePage $page
    ): void {
        if (!$page->ai_agent_id) {
            abort(422, 'This legacy knowledge page has not been assigned to an AI agent yet.');
        }

        $agent = AiAgent::query()->findOrFail($page->ai_agent_id);
        $this->authorizeAgentAccess($request, $agent);
    }

    private function authorizeSourceAccess(
        Request $request,
        KnowledgeSource $source
    ): void {
        if (!$source->ai_agent_id) {
            abort(422, 'This legacy knowledge source has not been assigned to an AI agent yet.');
        }

        $agent = AiAgent::query()->findOrFail($source->ai_agent_id);
        $this->authorizeAgentAccess($request, $agent);
    }

    private function resolveSourceType(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'pdf',
            'docx' => 'document',
            'csv', 'xlsx' => 'spreadsheet',
            'jpg', 'jpeg', 'png', 'webp' => 'image',
            default => 'text',
        };
    }
}
