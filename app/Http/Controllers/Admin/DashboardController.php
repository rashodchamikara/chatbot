<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\Website;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {

            $totalTenants = Tenant::count();
            $totalWebsites = Website::count();
            $totalLeads = Lead::count();
            $qualifiedLeads = Lead::where('status', 'qualified')->count();
            $totalConversations = Conversation::count();

            $recentLeads = Lead::with(['website', 'tenant'])
                ->latest()
                ->take(5)
                ->get();

            $recentConversations = Conversation::with(['website.tenant', 'lead'])
                ->latest()
                ->take(5)
                ->get();

        } else {

            $tenantId = $user->tenant_id;

            $websiteIds = Website::where('tenant_id', $tenantId)
                ->pluck('id');

            $totalTenants = null;

            $totalWebsites = Website::where('tenant_id', $tenantId)->count();

            $totalLeads = Lead::where('tenant_id', $tenantId)->count();

            $qualifiedLeads = Lead::where('tenant_id', $tenantId)
                ->where('status', 'qualified')
                ->count();

            $totalConversations = Conversation::whereIn('website_id', $websiteIds)
                ->count();

            $recentLeads = Lead::with('website')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->take(5)
                ->get();

            $recentConversations = Conversation::with(['website', 'lead'])
                ->whereIn('website_id', $websiteIds)
                ->latest()
                ->take(5)
                ->get();
        }

        return view('admin.dashboard', compact(
            'totalTenants',
            'totalWebsites',
            'totalLeads',
            'qualifiedLeads',
            'totalConversations',
            'recentLeads',
            'recentConversations'
        ));
    }
}