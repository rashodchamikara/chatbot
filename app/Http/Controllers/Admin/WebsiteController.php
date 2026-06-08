<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class WebsiteController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Website::with('tenant')
            ->withCount([
                'knowledgePages',
                'knowledgeChunks',
                'conversations',
                'leads',
            ]);

        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            }

            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $websites = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.websites.index', compact('websites'));
    }

    public function create()
    {
        $user = auth()->user();

        $tenants = collect();

        if ($user->isSuperAdmin()) {
            $tenants = Tenant::orderBy('name')->get();
        }

        return view('admin.websites.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:1000'],
            'verify_domain' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($user->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules);

        $tenantId = $user->isSuperAdmin()
            ? $validated['tenant_id']
            : $user->tenant_id;

        $domain = $this->normalizeDomain($validated['domain']);

        $website = Website::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'domain' => $domain,
            'verify_domain' => $request->boolean('verify_domain'),
            'is_active' => $request->boolean('is_active', true),
            'embed_token' => Str::random(48),
        ]);

        return redirect()
            ->route('admin.websites.show', $website)
            ->with('success', 'Website created successfully.');
    }

    public function show(Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        $website->load([
            'tenant',
            'knowledgePages' => function ($query) {
                $query->latest()->take(20);
            },
        ]);

        $website->loadCount([
            'knowledgePages',
            'knowledgeChunks',
            'conversations',
            'leads',
        ]);

        return view('admin.websites.show', compact('website'));
    }

    public function edit(Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        $tenants = collect();

        if (auth()->user()->isSuperAdmin()) {
            $tenants = Tenant::orderBy('name')->get();
        }

        return view('admin.websites.edit', compact('website', 'tenants'));
    }

    public function update(Request $request, Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:1000'],
            'verify_domain' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($user->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules);

        $website->name = $validated['name'];
        $website->domain = $this->normalizeDomain($validated['domain']);
        $website->verify_domain = $request->boolean('verify_domain');
        $website->is_active = $request->boolean('is_active');

        if ($user->isSuperAdmin()) {
            $website->tenant_id = $validated['tenant_id'];
        }

        $website->save();

        return redirect()
            ->route('admin.websites.show', $website)
            ->with('success', 'Website updated successfully.');
    }

    public function regenerateToken(Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        $website->embed_token = Str::random(48);
        $website->save();

        return redirect()
            ->back()
            ->with('success', 'Embed token regenerated successfully. Update the embed code on the client website.');
    }

    public function indexKnowledge(Request $request, Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = $request->input('limit', 20);

        Artisan::call('knowledge:index', [
            'website_id' => $website->id,
            '--limit' => $limit,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Website knowledge indexing completed.');
    }

    private function authorizeWebsiteAccess(Website $website): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($website->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized website access.');
        }
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);

        $domain = rtrim($domain, '/');

        return $domain;
    }
}
