<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgePage;
use App\Models\Website;
use App\Services\Knowledge\ManualKnowledgeIndexer;
use Illuminate\Http\Request;

class KnowledgePageController extends Controller
{
    public function index(Request $request, Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        $query = KnowledgePage::withCount('chunks')
            ->where('website_id', $website->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->filled('indexed')) {
            $query->where('is_indexed', $request->indexed === 'yes');
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === 'yes');
        }

        $pages = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.knowledge.index', compact('website', 'pages'));
    }

    public function create(Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        return view('admin.knowledge.create', compact('website'));
    }

    public function store(
        Request $request,
        Website $website,
        ManualKnowledgeIndexer $indexer,
    ) {
        $this->authorizeWebsiteAccess($website);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:page,product,blog,whitepaper,faq,service,pricing,other'],
            'content' => ['required', 'string', 'min:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $url = $validated['url']
            ?: 'manual://' . str()->slug($validated['title']) . '-' . time();

        $page = KnowledgePage::query()->create([
            'tenant_id' => $website->tenant_id,
            'ai_agent_id' => $website->ai_agent_id,
            'website_id' => $website->id,
            'url' => $url,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'source_type' => 'manual',
            'content' => $validated['content'],
            'content_hash' => hash('sha256', $validated['content']),
            'is_indexed' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($website->ai_agent_id) {
            $indexer->index($page);
        }

        return redirect()
            ->route('admin.knowledge.show', $page)
            ->with(
                'success',
                $website->ai_agent_id
                    ? 'Knowledge page created and indexed successfully.'
                    : 'Knowledge page created. The website must be linked to an AI agent before indexing.'
            );
    }

    public function show(KnowledgePage $knowledgePage)
    {
        $this->authorizeKnowledgePageAccess($knowledgePage);

        $knowledgePage->load([
            'website.tenant',
            'aiAgent',
            'chunks' => fn ($query) => $query->orderBy('chunk_index'),
        ]);

        return view('admin.knowledge.show', compact('knowledgePage'));
    }

    public function edit(KnowledgePage $knowledgePage)
    {
        $this->authorizeKnowledgePageAccess($knowledgePage);

        return view('admin.knowledge.edit', compact('knowledgePage'));
    }

    public function update(Request $request, KnowledgePage $knowledgePage)
    {
        $this->authorizeKnowledgePageAccess($knowledgePage);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:1000'],
            'type' => ['required', 'in:page,product,blog,whitepaper,faq,service,pricing,other'],
            'content' => ['required', 'string', 'min:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldHash = $knowledgePage->content_hash;
        $newHash = hash('sha256', $validated['content']);

        $knowledgePage->update([
            'title' => $validated['title'],
            'url' => $validated['url'],
            'type' => $validated['type'],
            'content' => $validated['content'],
            'content_hash' => $newHash,
            'is_active' => $request->boolean('is_active'),
            'is_indexed' => $oldHash === $newHash
                ? $knowledgePage->is_indexed
                : false,
            'indexed_at' => $oldHash === $newHash
                ? $knowledgePage->indexed_at
                : null,
        ]);

        if ($oldHash !== $newHash) {
            $knowledgePage->chunks()->delete();
        } else {
            $knowledgePage->chunks()->update([
                'is_active' => $knowledgePage->is_active,
            ]);
        }

        return redirect()
            ->route('admin.knowledge.show', $knowledgePage)
            ->with('success', 'Knowledge page updated successfully.');
    }

    public function destroy(KnowledgePage $knowledgePage)
    {
        $this->authorizeKnowledgePageAccess($knowledgePage);

        $website = $knowledgePage->website;
        $knowledgePage->chunks()->delete();
        $knowledgePage->delete();

        if ($website) {
            return redirect()
                ->route('admin.websites.knowledge.index', $website)
                ->with('success', 'Knowledge page deleted successfully.');
        }

        return redirect()
            ->route('admin.knowledge-hub.index')
            ->with('success', 'Knowledge page deleted successfully.');
    }

    public function indexPage(
        KnowledgePage $knowledgePage,
        ManualKnowledgeIndexer $indexer,
    ) {
        $this->authorizeKnowledgePageAccess($knowledgePage);
        $indexer->index($knowledgePage);

        return redirect()
            ->back()
            ->with('success', 'Knowledge page indexed successfully.');
    }

    public function toggleActive(KnowledgePage $knowledgePage)
    {
        $this->authorizeKnowledgePageAccess($knowledgePage);

        $knowledgePage->is_active = !$knowledgePage->is_active;
        $knowledgePage->save();

        $knowledgePage->chunks()->update([
            'is_active' => $knowledgePage->is_active && $knowledgePage->is_indexed,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Knowledge page status updated successfully.');
    }

    public function deleteAllForWebsite(Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        foreach ($website->knowledgePages()->get() as $page) {
            $page->chunks()->delete();
            $page->delete();
        }

        return redirect()
            ->route('admin.websites.show', $website)
            ->with('success', 'All knowledge pages for this website have been deleted.');
    }

    private function authorizeWebsiteAccess(Website $website): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $website->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Unauthorized website access.');
        }
    }

    private function authorizeKnowledgePageAccess(KnowledgePage $knowledgePage): void
    {
        if ($knowledgePage->website) {
            $this->authorizeWebsiteAccess($knowledgePage->website);
            return;
        }

        $user = auth()->user();

        if (!$user->isSuperAdmin()
            && (int) $knowledgePage->tenant_id !== (int) $user->tenant_id
        ) {
            abort(403, 'Unauthorized knowledge access.');
        }
    }
}
