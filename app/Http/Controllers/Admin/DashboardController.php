<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\Website;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $websiteIds = Website::where('tenant_id', $tenantId)
            ->pluck('id');

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

        return view('admin.dashboard', compact(
            'totalWebsites',
            'totalLeads',
            'qualifiedLeads',
            'totalConversations',
            'recentLeads',
            'recentConversations'
        ));
    }
}
