<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebsiteManagementController extends Controller
{
    public function index(Request $request): View
    {
        $websites = Website::query()
            ->with('tenant')
            ->withCount(['knowledgePages', 'knowledgeChunks', 'leads', 'conversations'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'active') $query->where('is_active', true);
                if ($request->status === 'suspended') $query->where('is_active', false);
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.system.websites.index', compact('websites'));
    }

    public function suspend(Request $request, Website $website, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $website->only(['is_active','suspended_at','suspended_by']);
        $website->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspended_by' => $request->user()->id,
        ]);
        $auditLogger->log($request, 'website.suspended', $website, $oldValues, $website->fresh()->only(['is_active','suspended_at','suspended_by']));
        return back()->with('success', "{$website->name} has been suspended.");
    }

    public function activate(Request $request, Website $website, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $website->only(['is_active','suspended_at','suspended_by']);
        $website->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);
        $auditLogger->log($request, 'website.activated', $website, $oldValues, $website->fresh()->only(['is_active','suspended_at','suspended_by']));
        return back()->with('success', "{$website->name} has been activated.");
    }

    public function destroy(Request $request, Website $website, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $website->only(['tenant_id','name','domain','is_active']);
        DB::transaction(function () use ($website, $request) {
            $website->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspended_by' => $request->user()->id,
            ]);
            $website->delete();
        });
        $auditLogger->log($request, 'website.deleted', $website, $oldValues, ['deleted_at' => now()->toDateTimeString()]);
        return back()->with('success', "{$website->name} has been deleted.");
    }
}
